<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasDynamicFilter
{
    public function applyFilters(
        Builder $query,
        Request $request,
        array $allowedFilters = [],
        array $searchableFields = []
    ): Builder {

        // FILTER DINAMIS
        foreach ($request->query() as $field => $value) {

            // skip reserved query
            if (in_array($field, [
                'search',
                'sort_by',
                'sort_order',
                'per_page',
                'page'
            ])) {
                continue;
            }

            // whitelist filter
            if (!empty($allowedFilters) && !in_array($field, $allowedFilters)) {
                continue;
            }

            // null support
            if ($value === 'null') {
                $query->whereNull($field);
                continue;
            }

            // boolean support
            if ($value === 'true') {
                $value = 1;
            }

            if ($value === 'false') {
                $value = 0;
            }

            // where in support
            if (str_contains($value, ',')) {

                $query->whereIn(
                    $field,
                    explode(',', $value)
                );

            } else {

                $query->where($field, $value);
            }
        }

        // SEARCH
        if (
            $request->filled('search') &&
            !empty($searchableFields)
        ) {

            $search = $request->search;

            $query->where(function ($q) use (
                $search,
                $searchableFields
            ) {

                foreach ($searchableFields as $field) {

                    $q->orWhere(
                        $field,
                        'like',
                        "%{$search}%"
                    );
                }
            });
        }

        // SORTING
        if ($request->filled('sort_by')) {

            $sortBy = $request->sort_by;

            $sortOrder = strtolower(
                $request->get('sort_order', 'asc')
            );

            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'asc';
            }

            $query->orderBy($sortBy, $sortOrder);

        } else {

            $query->latest();
        }

        return $query;
    }
}