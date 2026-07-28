<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ActiveCompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function gettingStarted(Request $request, ActiveCompanyContext $activeCompany): Response
    {
        $companyId = $activeCompany->companyId();
        $user = $request->user();

        $has = fn (string $model, string $column = 'company_id') => $companyId !== null
            && $model::query()->where($column, $companyId)->exists();

        $steps = [
            [
                'key' => 'company',
                'title' => 'Configura tu empresa',
                'description' => 'Datos fiscales, nombre comercial, moneda y zona horaria.',
                'completed' => $activeCompany->company() !== null,
                'href' => route('settings.company.edit'),
                'can' => $user->can('companies.manage'),
            ],
            [
                'key' => 'branch',
                'title' => 'Crea una sucursal',
                'description' => 'El lugar físico donde opera tu negocio.',
                'completed' => $has(Branch::class),
                'href' => route('settings.branches.index'),
                'can' => $user->can('branches.manage'),
            ],
            [
                'key' => 'warehouse',
                'title' => 'Crea un almacén',
                'description' => 'De aquí sale el inventario que vendes.',
                'completed' => $has(Warehouse::class),
                'href' => route('settings.warehouses.index'),
                'can' => $user->can('warehouses.manage'),
            ],
            [
                'key' => 'register',
                'title' => 'Crea una caja',
                'description' => 'La terminal donde se cobra en el punto de venta.',
                'completed' => $has(CashRegister::class),
                'href' => route('settings.registers.index'),
                'can' => $user->can('registers.manage'),
            ],
            [
                'key' => 'users',
                'title' => 'Crea usuarios',
                'description' => 'Da acceso a tu equipo de trabajo.',
                'completed' => $companyId !== null && User::query()->where('company_id', $companyId)->count() > 1,
                'href' => route('users.index'),
                'can' => $user->can('users.manage'),
            ],
            [
                'key' => 'roles',
                'title' => 'Asigna roles',
                'description' => 'Define qué puede hacer cada usuario.',
                'completed' => $companyId !== null && User::query()
                    ->where('company_id', $companyId)
                    ->whereHas('roles')
                    ->exists(),
                'href' => route('roles.index'),
                'can' => $user->can('roles.manage'),
            ],
            [
                'key' => 'products',
                'title' => 'Crea productos',
                'description' => 'El catálogo que vas a vender.',
                'completed' => $has(Product::class),
                'href' => route('products.index'),
                'can' => $user->can('products.view'),
            ],
            [
                'key' => 'inventory',
                'title' => 'Registra inventario',
                'description' => 'Captura la existencia inicial de tus productos.',
                'completed' => $has(InventoryMovement::class),
                'href' => route('inventory.balances.index'),
                'can' => $user->can('inventory.view'),
            ],
            [
                'key' => 'cash-session',
                'title' => 'Abre caja',
                'description' => 'Registra el fondo inicial para empezar a vender.',
                'completed' => $has(CashSession::class),
                'href' => route('cash.sessions.create'),
                'can' => $user->can('cash.open'),
            ],
            [
                'key' => 'sale',
                'title' => 'Realiza una venta',
                'description' => 'Prueba el punto de venta de principio a fin.',
                'completed' => $companyId !== null && Sale::query()
                    ->where('company_id', $companyId)
                    ->where('status', 'completed')
                    ->exists(),
                'href' => route('pos.index'),
                'can' => $user->can('pos.access'),
            ],
        ];

        return Inertia::render('Help/GettingStarted', [
            'steps' => $steps,
        ]);
    }

    public function guide(): Response
    {
        return Inertia::render('Help/Guide');
    }
}
