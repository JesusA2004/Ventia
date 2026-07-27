<?php

namespace App\Support;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Shapes a paginator into a plain array Inertia can serialize as-is.
 *
 * Laravel's automatic {data, links, meta} pagination wrapper only kicks in
 * via Resource::toResponse() on a top-level HTTP response, which never runs
 * for nested Inertia props — so paginated listings build that shape by hand.
 */
class PaginatedResource
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  LengthAwarePaginator<int, TModel>  $paginator
     * @param  class-string<JsonResource>  $resource
     * @return array{data: array<int, mixed>, links: array<int, mixed>, meta: array<string, mixed>}
     */
    public static function make(LengthAwarePaginator $paginator, string $resource): array
    {
        return [
            'data' => $resource::collection($paginator->items())->resolve(),
            'links' => $paginator->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
