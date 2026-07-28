<?php

namespace App\Actions\Cash;

use App\Enums\CashHandoverStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\CashHandover;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A supervisor's decision on a pending CashHandover. Approval is the only
 * path that actually closes the underlying session — it delegates to the
 * existing CloseCashSessionAction rather than duplicating its
 * ledger-writing logic, so the append-only cash_movements invariant holds
 * exactly as it does for an unsupervised close.
 */
class ResolveCashHandoverAction
{
    public function __construct(private readonly CloseCashSessionAction $closeSession) {}

    public function approve(CashHandover $handover, User $supervisor, ?string $notes): CashHandover
    {
        return DB::transaction(function () use ($handover, $supervisor, $notes) {
            $locked = $this->lockPending($handover);

            $session = $locked->cashSession;
            $this->closeSession->execute($session, (string) $locked->counted_cash, $locked->cashier_notes, $locked->cashier);

            $locked->update([
                'status' => CashHandoverStatus::Approved,
                'approved_by' => $supervisor->id,
                'supervisor_notes' => $notes,
                'resolved_at' => now(),
            ]);

            return $locked->refresh();
        });
    }

    public function reject(CashHandover $handover, User $supervisor, string $reason): CashHandover
    {
        $locked = $this->lockPending($handover);

        $locked->update([
            'status' => CashHandoverStatus::Rejected,
            'approved_by' => $supervisor->id,
            'supervisor_notes' => $reason,
            'resolved_at' => now(),
        ]);

        return $locked->refresh();
    }

    public function requestRecount(CashHandover $handover, User $supervisor, string $reason): CashHandover
    {
        $locked = $this->lockPending($handover);

        $locked->update([
            'status' => CashHandoverStatus::RecountRequested,
            'approved_by' => $supervisor->id,
            'supervisor_notes' => $reason,
            'resolved_at' => now(),
        ]);

        return $locked->refresh();
    }

    private function lockPending(CashHandover $handover): CashHandover
    {
        /** @var CashHandover $locked */
        $locked = CashHandover::query()->whereKey($handover->id)->lockForUpdate()->firstOrFail();

        if ($locked->status !== CashHandoverStatus::Pending) {
            throw new InvalidStateTransitionException('la entrega de caja', $locked->status->value, 'resolver');
        }

        return $locked;
    }
}
