<?php

namespace App\Support\Reports;

class ReportSheet
{
    /**
     * @param  array<int, array{key: string, label: string, money?: bool, qty?: bool, date?: bool, stock?: bool}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $summary
     * @param  array<int, array{title?: string, columns: array, rows: array}>  $sections
     */
    public function __construct(
        public string $title,
        public string $subtitle = '',
        public array $columns = [],
        public array $rows = [],
        public array $summary = [],
        public array $sections = [],
        public string $empty = 'Tidak ada data pada filter ini.',
    ) {
    }

    public function blocks(): array
    {
        if ($this->sections !== []) {
            return $this->sections;
        }

        return [[
            'title' => null,
            'columns' => $this->columns,
            'rows' => $this->rows,
        ]];
    }
}
