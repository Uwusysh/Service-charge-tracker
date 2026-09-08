@props(['title', 'lede' => null])

<div class="setk-page-head">
    <div>
        <h1>{{ $title }}</h1>
        @if($lede)
            <p class="lede">{{ $lede }}</p>
        @endif
        {{ $slot }}
    </div>
    @isset($actions)
        <div class="setk-actions">{{ $actions }}</div>
    @endisset
</div>
