<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRegisterRequest;
use App\Http\Requests\Settings\UpdateRegisterRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\CashRegisterResource;
use App\Http\Resources\UserResource;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->authorizeResource(CashRegister::class, 'register');
    }

    public function index(Request $request): Response
    {
        $registers = CashRegister::query()
            ->with(['branch:id,name', 'assignedUser:id,name'])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Registers/Index', [
            'registers' => PaginatedResource::make($registers, CashRegisterResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Registers/Create', $this->formOptions());
    }

    public function store(StoreRegisterRequest $request): RedirectResponse
    {
        $register = CashRegister::create($request->validated());

        $this->audit->log('registers', 'created', "Creó la caja «{$register->name}» ({$register->code}).", $register, branchId: $register->branch_id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caja creada correctamente.']);

        return to_route('settings.registers.index');
    }

    public function edit(CashRegister $register): Response
    {
        return Inertia::render('Admin/Registers/Edit', [
            'register' => CashRegisterResource::make($register),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateRegisterRequest $request, CashRegister $register): RedirectResponse
    {
        $before = $register->only(['name', 'status', 'has_cash_drawer']);
        $register->update($request->validated());

        $this->audit->log('registers', 'updated', "Actualizó la caja «{$register->name}».", $register, $before, $register->only(['name', 'status', 'has_cash_drawer']), branchId: $register->branch_id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caja actualizada correctamente.']);

        return to_route('settings.registers.index');
    }

    public function destroy(CashRegister $register): RedirectResponse
    {
        $name = $register->name;
        $branchId = $register->branch_id;
        $register->delete();

        $this->audit->log('registers', 'deleted', "Eliminó la caja «{$name}».", $register, branchId: $branchId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caja eliminada correctamente.']);

        return to_route('settings.registers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branches' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
            'users' => UserResource::collection(User::query()->orderBy('name')->get()),
        ];
    }
}
