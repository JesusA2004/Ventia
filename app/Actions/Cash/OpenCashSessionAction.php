<?php

namespace App\Actions\Cash;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Exceptions\CashSessionConflictException;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * A register or a user can only have one open session at a time. Since
 * "no second open row" can't be expressed as a DB unique constraint here
 * (status is just one of several values), we serialize concurrent opens by
 * locking the register and user rows themselves before checking.
 */
class OpenCashSessionAction
{
    public function __construct(private readonly RegisterCashMovementAction $recorder) {}

    public function execute(CashRegister $register, User $user, string $openingAmount, ?string $notes): CashSession
    {
        return DB::transaction(function () use ($register, $user, $openingAmount, $notes) {
            CashRegister::query()->whereKey($register->id)->lockForUpdate()->firstOrFail();
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $conflicting = CashSession::query()
                ->where('register_id', $register->id)
                ->where('status', CashSessionStatus::Open)
                ->with('user:id,name')
                ->first();

            if ($conflicting !== null) {
                $holder = $conflicting->user?->name ?? 'otro usuario';

                throw new CashSessionConflictException("La caja «{$register->name}» ya está siendo utilizada por {$holder}.");
            }

            if (CashSession::query()->where('user_id', $user->id)->where('status', CashSessionStatus::Open)->exists()) {
                throw new CashSessionConflictException('Ya tienes una sesión de caja abierta. Continúa al punto de venta en lugar de abrir una nueva.');
            }

            $amount = Decimal::of($openingAmount);

            if (bccomp($amount, '0', 4) < 0) {
                throw new InvalidArgumentException('El fondo inicial no puede ser negativo.');
            }

            $session = CashSession::query()->create([
                'company_id' => $register->company_id,
                'branch_id' => $register->branch_id,
                'register_id' => $register->id,
                'user_id' => $user->id,
                'status' => CashSessionStatus::Open,
                'opened_at' => now(),
                'opening_amount' => $amount,
                'opening_notes' => $notes,
            ]);

            $this->recorder->execute($session, CashMovementType::Opening, $amount, 'Fondo inicial de caja', $user);

            return $session;
        });
    }
}
