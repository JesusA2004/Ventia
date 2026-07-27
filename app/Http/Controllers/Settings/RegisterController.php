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
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct()
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
        CashRegister::create($request->validated());

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
        $register->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caja actualizada correctamente.']);

        return to_route('settings.registers.index');
    }

    public function destroy(CashRegister $register): RedirectResponse
    {
        $register->delete();

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
