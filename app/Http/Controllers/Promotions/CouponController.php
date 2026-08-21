<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupons\StoreCouponRequest;
use App\Http\Requests\Coupons\UpdateCouponRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CouponResource;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Coupon;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->authorizeResource(Coupon::class, 'coupon');
    }

    public function index(Request $request): Response
    {
        $coupons = Coupon::query()
            ->withCount('completedSales')
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where(fn ($sub) => $sub
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
            ))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Promotions/Coupons/Index', [
            'coupons' => PaginatedResource::make($coupons, CouponResource::class),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Promotions/Coupons/Create', $this->formOptions());
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $coupon = DB::transaction(function () use ($request) {
            $coupon = Coupon::create($request->safe()->except(['branch_ids', 'product_ids', 'category_ids']));
            $coupon->branches()->sync($request->validated('branch_ids', []));
            $coupon->products()->sync($request->validated('product_ids', []));
            $coupon->categories()->sync($request->validated('category_ids', []));

            return $coupon;
        });

        $this->audit->log('coupons', 'created', "Creó el cupón «{$coupon->code}».", $coupon);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cupón creado correctamente.']);

        return to_route('promotions.coupons.index');
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('Promotions/Coupons/Edit', [
            'coupon' => CouponResource::make($coupon->load(['branches:id,name', 'products:id,name,sku', 'categories:id,name'])),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $before = $coupon->only(['code', 'name', 'type', 'value', 'status']);

        DB::transaction(function () use ($request, $coupon) {
            $coupon->update($request->safe()->except(['branch_ids', 'product_ids', 'category_ids']));
            $coupon->branches()->sync($request->validated('branch_ids', []));
            $coupon->products()->sync($request->validated('product_ids', []));
            $coupon->categories()->sync($request->validated('category_ids', []));
        });

        $this->audit->log('coupons', 'updated', "Actualizó el cupón «{$coupon->code}».", $coupon, $before, $coupon->only(['code', 'name', 'type', 'value', 'status']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cupón actualizado correctamente.']);

        return to_route('promotions.coupons.index');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $code = $coupon->code;
        $coupon->delete();

        $this->audit->log('coupons', 'deleted', "Eliminó el cupón «{$code}».", $coupon);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cupón eliminado correctamente.']);

        return to_route('promotions.coupons.index');
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
