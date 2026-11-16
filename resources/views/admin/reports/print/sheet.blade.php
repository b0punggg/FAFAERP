<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $sheet->title }}</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; color: #1f2933; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .sub { color: #52606d; margin: 0 0 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12px; }
        th, td { border-bottom: 1px solid #d9e2ec; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f5f7fa; }
        .num { text-align: right; white-space: nowrap; }
        h2 { font-size: 14px; margin: 16px 0 6px; }
        .sum { margin-top: 8px; }
        .sum div { display: flex; justify-content: space-between; max-width: 360px; padding: 4px 0; }
        button { margin-top: 16px; padding: 8px 14px; }
        @media print { button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <h1>{{ $sheet->title }}</h1>
    @if($sheet->subtitle)<p class="sub">{{ $sheet->subtitle }}</p>@endif
    @foreach($sheet->blocks() as $block)
        @if(!empty($block['title']))<h2>{{ $block['title'] }}</h2>@endif
        <table>
            <thead>
                <tr>
                    @foreach($block['columns'] as $column)
                        <th class="{{ !empty($column['money']) || !empty($column['qty']) || !empty($column['stock']) ? 'num' : '' }}">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse($block['rows'] as $row)
                <tr>
                    @foreach($block['columns'] as $column)
                        <td class="{{ !empty($column['money']) || !empty($column['qty']) || !empty($column['stock']) ? 'num' : '' }}">{{ \App\Support\Reports\ReportOutput::display($row[$column['key']] ?? '', $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(count($block['columns']), 1) }}">{{ $sheet->empty }}</td></tr>
            @endforelse
            </tbody>
        </table>
    @endforeach
    @if($sheet->summary)
        <div class="sum">
            @foreach($sheet->summary as $label => $value)
                <div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
            @endforeach
        </div>
    @endif
    <button onclick="window.print()">Cetak</button>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
