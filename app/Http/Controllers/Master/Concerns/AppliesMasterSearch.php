<?php

namespace App\Http\Controllers\Master\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesMasterSearch
{
    protected function applyMasterSearch(Builder $query, Request $request, array $columns): Builder
    {
        $term = trim((string) $request->get('q', ''));
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$term}%");
            }
        });
    }
}
