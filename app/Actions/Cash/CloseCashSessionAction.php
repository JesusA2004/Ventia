<?php

namespace App\Actions\Cash;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\CashSession;
use App\Models\User;
use App\Services\Cash\CalculateExpectedCashService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CloseCashSessionAction
{
    public function __construct(
        private readonly CalculateExpectedCashService $expectedCash,
        private readonly RegisterCashMovementAction $recorder,
    ) {}

    public function execute(CashSession $session, string $countedCash, ?string $closingNotes, User $closedBy, bool $forced = false): CashSession
    {
        return DB::transaction(function () use ($session, $countedCash, $closingNotes, $closedBy, $forced) {
            /** @var CashSession $locked */
            $locked = CashSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== CashSessionStatus::Open) {
                throw new InvalidStateTransitionException('la sesión de caja', $locked->status->value, 'cerrar');
            }

            $counted = Decimal::of($countedCash);

            if (bccomp($counted, '0', 4) < 0) {
                throw new InvalidArgumentException('El efectivo contado no puede ser negativo.');
            }

            $expected = $this->expectedCash->calculate($locked);
            $difference = bcsub($counted, $expected, 4);

            // Recorded while the session is still Open: RegisterCashMovementAction
            // itself enforces "no movements on a closed session", so the closing
            // withdrawal must happen before the status flips.
            if (bccomp($counted, '0', 4) > 0) {
                $this->recorder->execute($locked, CashMovementType::Closing, $counted, 'Retiro de efectivo por cierre de caja', $closedBy);
            }

            $locked->update([
                'status' => $forced ? CashSessionStatus::ForceClosed : CashSessionStatus::Closed,
                'expected_cash' => $expected,
                'counted_cash' => $counted,
                'difference' => $difference,
                'closed_at' => now(),
                'closed_by' => $closedBy->id,
                'closing_notes' => $closingNotes,
            ]);

            return $locked->refresh();
        });
    }
}
