<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('companies.manage');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('companies.manage') && $user->company_id === $company->id;
    }

    public function create(User $user): bool
    {
        // Only superadministrators create new companies (Gate::before already
        // grants them access); regular admins manage only their own company.
        return false;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.manage') && $user->company_id === $company->id;
    }

    public function delete(User $user, Company $company): bool
    {
        return false;
    }
}
