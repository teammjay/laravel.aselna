<?php

namespace App\Services\DataTable;

use Illuminate\Database\Eloquent\Builder;

class DataTableService
{
    protected $query;
    protected $request;

    protected $disableSearch = false;
    protected $customSearch;

    protected $customSorting;

    public function __construct(Builder $query, $request)
    {
        $this->query = $query;
        $this->request = $request;
    }

    public function handle()
    {
        // Apply search logic
        $this->applySearch();

        // Apply sorting logic
        $this->applySorting();

        // Apply pagination logic
        return $this->applyPagination();
    }

    public function setCustomSearch(callable $callback)
    {
        $this->customSearch = $callback;
    }

    public function setCustomSorting(callable $callback)
    {
        $this->customSorting = $callback;
    }

    public function disableSearch()
    {
        $this->disableSearch = true;
    }

    protected function applySearch()
    {
        if ($this->disableSearch) return;
        if ($this->customSearch) {
            call_user_func($this->customSearch, $this->query, $this->request);
        } elseif ($search = $this->request->input('search.value')) {
            $this->query->where(function ($query) use ($search) {
                // get all the columns from request
                $columns = $this->request->input('columns');
                foreach ($columns as $column) {
                    if ($column['searchable'] == 'true' && isset($column['data']) && $column['data'] != 'action') {
                        $query->orWhere($column['data'], 'like', '%' . $search . '%');
                    }
                }
            });
        }
    }

    protected function applySorting()
    {
        if ($this->customSorting) {
            call_user_func($this->customSorting, $this->query, $this->request);
        } else {
            $columnIndex = $this->request->input('order.0.column', 0);
            $sortColumn = $this->request->input('columns.' . $columnIndex . '.data', 'id');
            $sortDirection = $this->request->input('order.0.dir', 'asc');
            $this->query->orderBy($sortColumn, $sortDirection);
        }
    }

    protected function applyPagination()
    {
        $length = $this->request->input('length', 10);
        $start = $this->request->input('start', 0);
        $page = ($start / $length) + 1;

        return $this->query->paginate($length, ['*'], 'page', $page);
    }
}
