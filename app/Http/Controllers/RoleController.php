<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Support\PermissionLabels;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:roles.manage');
    }

    public function index(): Response
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'editable' => $role->name !== 'Superadministrador',
            ]);

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): Response
    {
        if ($role->name === 'Superadministrador') {
            abort(403, 'El rol Superadministrador no es editable: tiene acceso total por diseño.');
        }

        $rolePermissions = $role->permissions()->pluck('name');

        $groups = Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0])
            ->map(fn ($permissions, $groupKey) => [
                'label' => PermissionLabels::group($groupKey),
                'permissions' => $permissions->map(fn (Permission $permission) => [
                    'name' => $permission->name,
                    'label' => PermissionLabels::label($permission->name),
                    'description' => PermissionLabels::description($permission->name),
                    'granted' => $rolePermissions->contains($permission->name),
                ])->values(),
            ])
            ->sortBy('label')
            ->values();

        return Inertia::render('Roles/Edit', [
            'role' => ['id' => $role->id, 'name' => $role->name],
            'permissionGroups' => $groups,
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === 'Superadministrador') {
            abort(403, 'El rol Superadministrador no es editable: tiene acceso total por diseño.');
        }

        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permisos del rol actualizados correctamente.']);

        return to_route('roles.index');
    }
}
