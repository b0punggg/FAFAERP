@foreach($sheet->blocks() as $block)
<div class="fafa-card mb-3">
    @if(!empty($block['title']))<div class="fafa-card-header">{{ $block['title'] }}</div>@endif
    <div class="fafa-card-body" style="padding:0">
        <div class="report-scroll">
            <table class="fafa-table">
                <thead>
                    <tr>
                        @foreach($block['columns'] as $column)
                            <th class="{{ !empty($column['money']) || !empty($column['qty']) || !empty($column['stock']) ? 'text-right' : '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse($block['rows'] as $row)
                    <tr>
                        @foreach($block['columns'] as $column)
                            @php $raw = $row[$column['key']] ?? null; $number = is_numeric($raw) ? (float) $raw : null; @endphp
                            <td class="{{ !empty($column['money']) || !empty($column['qty']) || !empty($column['stock']) ? 'text-right' : '' }}">
                                @if(!empty($column['stock']) && $number !== null && $number <= 0)
                                    <span class="badge-fafa badge-danger">{{ \App\Support\Reports\ReportOutput::display($raw, $column) }}</span>
                                @elseif(!empty($column['stock']) && $number !== null && $number <= 5)
                                    <span class="badge-fafa badge-warning">{{ \App\Support\Reports\ReportOutput::display($raw, $column) }}</span>
                                @else
                                    {{ \App\Support\Reports\ReportOutput::display($raw, $column) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(count($block['columns']), 1) }}" class="text-center text-muted">{{ $sheet->empty }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach
