<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('units.manage');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('units.manage') && $this->belongsToUserOrIsGlobal($user, $unit);
    }

    public function create(User $user): bool
    {
        return $user->can('units.manage');
    }

    public function update(User $user, Unit $unit): bool
    {
        // Superadministrators bypass via Gate::before; regular company users
        // may only edit their own custom units, never the system-wide ones.
        return $user->can('units.manage') && $unit->company_id === $user->company_id;
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('units.manage') && $unit->company_id === $user->company_id;
    }

    private function belongsToUserOrIsGlobal(User $user, Unit $unit): bool
    {
        return $unit->company_id === null || $unit->company_id === $user->company_id;
    }
}
