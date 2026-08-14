<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreTaxRequest;
use App\Http\Requests\Catalog\UpdateTaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->authorizeResource(Tax::class, 'tax');
    }

    public function index(Request $request): Response
    {
        $taxes = Tax::query()
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Taxes/Index', [
            'taxes' => PaginatedResource::make($taxes, TaxResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/Taxes/Create');
    }

    public function store(StoreTaxRequest $request): RedirectResponse
    {
        $tax = Tax::create($request->validated());

        $this->audit->log('taxes', 'created', "Creó el impuesto «{$tax->name}».", $tax);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Impuesto creado correctamente.']);

        return to_route('catalog.taxes.index');
    }

    public function edit(Tax $tax): Response
    {
        return Inertia::render('Catalog/Taxes/Edit', [
            'tax' => TaxResource::make($tax),
        ]);
    }

    public function update(UpdateTaxRequest $request, Tax $tax): RedirectResponse
    {
        $before = $tax->only(['name', 'rate', 'status']);
        $tax->update($request->validated());

        $this->audit->log('taxes', 'updated', "Actualizó el impuesto «{$tax->name}».", $tax, $before, $tax->only(['name', 'rate', 'status']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Impuesto actualizado correctamente.']);

        return to_route('catalog.taxes.index');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        if ($tax->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'tax' => 'No se puede eliminar: el impuesto tiene productos relacionados.',
            ]);
        }

        $name = $tax->name;
        $tax->delete();

        $this->audit->log('taxes', 'deleted', "Eliminó el impuesto «{$name}».", $tax);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Impuesto eliminado correctamente.']);

        return to_route('catalog.taxes.index');
    }
}
