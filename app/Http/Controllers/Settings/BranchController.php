<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreBranchRequest;
use App\Http\Requests\Settings\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\ActiveCompanyContext;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use App\Support\SequentialCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function __construct(
        private readonly ActiveCompanyContext $activeCompany,
        private readonly AuditLogger $audit,
    ) {
        $this->authorizeResource(Branch::class, 'branch');
    }

    public function index(Request $request): Response
    {
        $branches = Branch::query()
            ->withCount(['warehouses', 'registers'])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Branches/Index', [
            'branches' => PaginatedResource::make($branches, BranchResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Branches/Create', [
            'suggestedCode' => SequentialCodeGenerator::next(Branch::class, 'SUC', ['company_id' => $this->activeCompany->companyId()]),
        ]);
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = Branch::create($request->validated());

        $this->audit->log('branches', 'created', "Creó la sucursal «{$branch->name}» ({$branch->code}).", $branch, null, $branch->only(['name', 'code', 'status']), branchId: $branch->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sucursal creada correctamente.']);

        return to_route('settings.branches.index');
    }

    public function edit(Branch $branch): Response
    {
        return Inertia::render('Admin/Branches/Edit', [
            'branch' => BranchResource::make($branch),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $before = $branch->only(['name', 'code', 'address', 'phone', 'status']);
        $branch->update($request->validated());

        $this->audit->log('branches', 'updated', "Actualizó la sucursal «{$branch->name}» ({$branch->code}).", $branch, $before, $branch->only(['name', 'code', 'address', 'phone', 'status']), branchId: $branch->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sucursal actualizada correctamente.']);

        return to_route('settings.branches.index');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $name = $branch->name;
        $branch->delete();

        $this->audit->log('branches', 'deleted', "Eliminó la sucursal «{$name}».", $branch, branchId: $branch->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sucursal eliminada correctamente.']);

        return to_route('settings.branches.index');
    }
}
