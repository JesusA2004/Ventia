<?php

namespace App\Services;

use App\Enums\Status;
use App\Exceptions\NoActiveCompanySelectedException;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * Resolves which company the current request acts on. Regular users always
 * act on their own company_id. Superadministrators have no company_id of
 * their own, so their active company comes from a session selection made
 * via the company switcher — never assume it, and never let a null value
 * silently reach a non-nullable consumer like SettingsService.
 */
class ActiveCompanyContext
{
    private bool $resolved = false;

    private ?Company $company = null;

    public function __construct(
        private readonly Request $request,
    ) {}

    public function isSuperAdmin(): bool
    {
        return $this->request->user()?->isSuperAdmin() ?? false;
    }

    public function companyId(): ?int
    {
        return $this->company()?->id;
    }

    public function company(): ?Company
    {
        if ($this->resolved) {
            return $this->company;
        }

        $this->resolved = true;
        $user = $this->request->user();

        if ($user === null) {
            return $this->company = null;
        }

        if (! $user->isSuperAdmin()) {
            return $this->company = $user->company;
        }

        $selectedId = $this->request->session()->get('active_company_id');

        if ($selectedId === null) {
            return $this->company = null;
        }

        $company = Company::query()->find((int) $selectedId);

        if ($company === null || $company->status !== Status::Active) {
            return $this->company = null;
        }

        return $this->company = $company;
    }

    /**
     * @throws NoActiveCompanySelectedException
     */
    public function requireCompanyId(): int
    {
        return $this->companyId() ?? throw new NoActiveCompanySelectedException;
    }

    public function select(int $companyId): void
    {
        $this->request->session()->put('active_company_id', $companyId);
        $this->resolved = false;
        $this->company = null;
    }

    public function clear(): void
    {
        $this->request->session()->forget('active_company_id');
        $this->resolved = false;
        $this->company = null;
    }
}
