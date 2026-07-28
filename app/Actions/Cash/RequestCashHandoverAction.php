<?php

namespace App\Actions\Cash;

use App\Enums\CashHandoverStatus;
use App\Enums\CashSessionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\CashHandover;
use App\Models\CashSession;
use App\Models\User;
use App\Services\Cash\CalculateExpectedCashService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Starts the supervised handover flow: the cashier counts denominations,
 * the system snapshots the expected cash and computes the difference, and a
 * pending CashHandover is created for a supervisor to review. The session
 * itself is NOT closed here — that only happens once ResolveCashHandoverAction
 * approves it, which is what keeps this additive to the existing
 * CloseCashSessionAction rather than a replacement for it.
 */
class RequestCashHandoverAction
{
    public function __construct(private readonly CalculateExpectedCashService $expectedCash) {}

    /**
     * @param  list<array{denomination: numeric-string, quantity: int}>  $denominations
     */
    public function execute(CashSession $session, array $denominations, ?string $cashierNotes, User $cashier): CashHandover
    {
        return DB::transaction(function () use ($session, $denominations, $cashierNotes, $cashier) {
            /** @var CashSession $locked */
            $locked = CashSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== CashSessionStatus::Open) {
                throw new InvalidStateTransitionException('la sesión de caja', $locked->status->value, 'cerrar');
            }

            $existingPending = CashHandover::query()
                ->where('cash_session_id', $locked->id)
                ->where('status', CashHandoverStatus::Pending)
                ->exists();

            if ($existingPending) {
                throw new InvalidArgumentException('Ya existe una entrega de caja pendiente de revisión para esta sesión.');
            }

            $counted = '0.0000';
            foreach ($denominations as $line) {
                $counted = bcadd($counted, bcmul(Decimal::of((string) $line['denomination']), (string) $line['quantity'], 4), 4);
            }

            $expected = $this->expectedCash->calculate($locked);
            $difference = bcsub($counted, $expected, 4);

            return CashHandover::create([
                'company_id' => $locked->company_id,
                'branch_id' => $locked->branch_id,
                'cash_session_id' => $locked->id,
                'cashier_id' => $cashier->id,
                'status' => CashHandoverStatus::Pending,
                'expected_cash' => $expected,
                'counted_cash' => $counted,
                'difference' => $difference,
                'denominations' => $denominations,
                'cashier_notes' => $cashierNotes,
                'requested_at' => now(),
            ]);
        });
    }
}
