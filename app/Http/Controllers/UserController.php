<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\UserResource;
use App\Models\Branch;
use App\Models\User;
use App\Services\ActiveCompanyContext;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly ActiveCompanyContext $activeCompany,
        private readonly AuditLogger $audit,
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): Response
    {
        $users = User::query()
            ->when(
                ! $request->user()->isSuperAdmin(),
                fn (Builder $query) => $query->where('company_id', $request->user()->company_id),
            )
            ->with('roles:id,name')
            ->when($request->string('search')->toString(), fn (Builder $query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => PaginatedResource::make($users, UserResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', $this->formOptions());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                ...$request->safe()->except(['password', 'role', 'branch_ids']),
                'company_id' => $this->activeCompany->requireCompanyId(),
                'password' => $request->validated('password'),
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$request->validated('role')]);
            $user->branches()->sync($request->validated('branch_ids', []));

            return $user;
        });

        $this->audit->log(
            'users', 'created',
            "Creó el usuario «{$user->name}» ({$user->email}) con rol {$request->validated('role')}.",
            $user,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado correctamente.']);

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        $user->load('branches:id,name');

        return Inertia::render('Users/Edit', [
            'targetUser' => UserResource::make($user),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $before = [...$user->only(['name', 'email', 'status']), 'role' => $user->roles->pluck('name')->first()];

        DB::transaction(function () use ($request, $user) {
            $user->update([
                ...$request->safe()->except(['password', 'role', 'branch_ids']),
                ...$request->validated('password') ? ['password' => $request->validated('password')] : [],
            ]);

            $user->syncRoles([$request->validated('role')]);
            $user->branches()->sync($request->validated('branch_ids', []));
        });

        $this->audit->log(
            'users', 'updated',
            "Actualizó el usuario «{$user->name}» ({$user->email}).",
            $user,
            $before,
            [...$user->only(['name', 'email', 'status']), 'role' => $request->validated('role')],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado correctamente.']);

        return to_route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $name = $user->name;
        $email = $user->email;
        $user->delete();

        $this->audit->log('users', 'deactivated', "Desactivó al usuario «{$name}» ({$email}).", $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario desactivado correctamente.']);

        return to_route('users.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'roles' => Role::query()->where('name', '!=', 'Superadministrador')->orderBy('name')->pluck('name'),
            'branches' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
        ];
    }
}
