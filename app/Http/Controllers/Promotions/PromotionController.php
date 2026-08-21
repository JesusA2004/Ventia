<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\StorePromotionRequest;
use App\Http\Requests\Promotions\UpdatePromotionRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PromotionResource;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Promotion;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->authorizeResource(Promotion::class, 'promotion');
    }

    public function index(Request $request): Response
    {
        $promotions = Promotion::query()
            ->withCount('completedSales')
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Promotions/Promotions/Index', [
            'promotions' => PaginatedResource::make($promotions, PromotionResource::class),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Promotions/Promotions/Create', $this->formOptions());
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $promotion = DB::transaction(function () use ($request) {
            $promotion = Promotion::create($request->safe()->except(['branch_ids', 'product_ids', 'category_ids']));
            $promotion->branches()->sync($request->validated('branch_ids', []));
            $promotion->products()->sync($request->validated('product_ids', []));
            $promotion->categories()->sync($request->validated('category_ids', []));

            return $promotion;
        });

        $this->audit->log('promotions', 'created', "Creó la promoción «{$promotion->name}».", $promotion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promoción creada correctamente.']);

        return to_route('promotions.promotions.index');
    }

    public function edit(Promotion $promotion): Response
    {
        return Inertia::render('Promotions/Promotions/Edit', [
            'promotion' => PromotionResource::make($promotion->load(['branches:id,name', 'products:id,name,sku', 'categories:id,name'])),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $before = $promotion->only(['name', 'type', 'value', 'status']);

        DB::transaction(function () use ($request, $promotion) {
            $promotion->update($request->safe()->except(['branch_ids', 'product_ids', 'category_ids']));
            $promotion->branches()->sync($request->validated('branch_ids', []));
            $promotion->products()->sync($request->validated('product_ids', []));
            $promotion->categories()->sync($request->validated('category_ids', []));
        });

        $this->audit->log('promotions', 'updated', "Actualizó la promoción «{$promotion->name}».", $promotion, $before, $promotion->only(['name', 'type', 'value', 'status']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promoción actualizada correctamente.']);

        return to_route('promotions.promotions.index');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $name = $promotion->name;
        $promotion->delete();

        $this->audit->log('promotions', 'deleted', "Eliminó la promoción «{$name}».", $promotion);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promoción eliminada correctamente.']);

        return to_route('promotions.promotions.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branchOptions' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
            'categoryOptions' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
        ];
    }
}
