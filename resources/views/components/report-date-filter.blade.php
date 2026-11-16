@php
    $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
@endphp
@if(isset($month) && isset($year))
<label class="report-field">Bulan
    <select name="month" class="form-control">
        @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" @selected((int) $month === $m)>{{ $namaBulan[$m] }}</option>
        @endfor
    </select>
</label>
<label class="report-field">Tahun
    <input type="number" name="year" value="{{ $year }}" class="form-control" min="2000" max="2100">
</label>
@else
<label class="report-field">Dari
    <input type="date" name="from" value="{{ $from }}" class="form-control">
</label>
<label class="report-field">Sampai
    <input type="date" name="to" value="{{ $to }}" class="form-control">
</label>
@endif
