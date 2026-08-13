<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreCategoryRequest;
use App\Http\Requests\Catalog\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount('products')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Categories/Index', [
            'categories' => PaginatedResource::make($categories, CategoryResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/Categories/Create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create([...$request->validated(), 'slug' => str($request->validated('name'))->slug()]);

        $this->audit->log('categories', 'created', "Creó la categoría «{$category->name}».", $category);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría creada correctamente.']);

        return to_route('catalog.categories.index');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Catalog/Categories/Edit', [
            'category' => CategoryResource::make($category),
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $before = $category->only(['name', 'parent_id', 'status']);
        $category->update([...$request->validated(), 'slug' => str($request->validated('name'))->slug()]);

        $this->audit->log('categories', 'updated', "Actualizó la categoría «{$category->name}».", $category, $before, $category->only(['name', 'parent_id', 'status']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría actualizada correctamente.']);

        return to_route('catalog.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->withTrashed()->exists() || $category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'No se puede eliminar: la categoría tiene productos o subcategorías relacionadas.',
            ]);
        }

        $name = $category->name;
        $category->delete();

        $this->audit->log('categories', 'deleted', "Eliminó la categoría «{$name}».", $category);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría eliminada correctamente.']);

        return to_route('catalog.categories.index');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function parentOptions(?Category $excluding = null): array
    {
        return Category::query()
            ->when($excluding, fn ($query) => $query->whereKeyNot($excluding->id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])
            ->all();
    }
}
