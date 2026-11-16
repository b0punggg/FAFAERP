@props(['title', 'description' => null, 'actionUrl' => null, 'actionLabel' => null])

<div class="fafa-page-header d-flex flex-wrap align-items-start justify-content-between gap-2">
    <div>
        <h1>{{ $title }}</h1>
        @if($description)<p>{{ $description }}</p>@endif
    </div>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn-fafa btn-fafa-primary">
            <i class="fa fa-plus"></i> {{ $actionLabel }}
        </a>
    @endif
</div>
