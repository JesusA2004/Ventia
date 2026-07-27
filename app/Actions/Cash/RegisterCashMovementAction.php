<?php

namespace App\Actions\Cash;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single entry point for touching cash_movements. Append-only ledger:
 * nothing else in the codebase should insert into this table directly.
 * Reversals are a new movement with the opposite type, never an edit.
 */
class RegisterCashMovementAction
{
    public function execute(
        CashSession $session,
        CashMovementType $type,
        string $amount,
        string $reason,
        User $user,
        ?string $notes = null,
        ?Model $reference = null,
    ): CashMovement {
        return DB::transaction(function () use ($session, $type, $amount, $reason, $user, $notes, $reference) {
            /** @var CashSession $locked */
            $locked = CashSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== CashSessionStatus::Open) {
                throw new InvalidStateTransitionException('la sesión de caja', $locked->status->value, 'registrar un movimiento');
            }

            $value = Decimal::of($amount);

            if (bccomp($value, '0', 4) < 0) {
                throw new InvalidArgumentException('El monto del movimiento no puede ser negativo.');
            }

            return CashMovement::query()->create([
                'company_id' => $locked->company_id,
                'branch_id' => $locked->branch_id,
                'register_id' => $locked->register_id,
                'cash_session_id' => $locked->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $value,
                'reason' => $reason,
                'notes' => $notes,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'occurred_at' => now(),
            ]);
        });
    }
}
