@if(!empty($sheet->summary))
<div class="report-summary">
    @foreach($sheet->summary as $label => $value)
        <div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
    @endforeach
</div>
@endif
