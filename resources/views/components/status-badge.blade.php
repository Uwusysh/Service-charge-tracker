@props(['status'])

@php
    $map = [
        'draft' => 'badge-draft',
        'ready_for_review' => 'badge-ready',
        'final' => 'badge-final',
        'archived' => 'badge-archived',
        'submitted' => 'badge-submitted',
        'approved' => 'badge-approved',
        'rejected' => 'badge-rejected',
        'active' => 'badge-final',
        'closed' => 'badge-archived',
    ];
    $class = $map[$status] ?? 'badge-neutral';
    $label = str_replace('_', ' ', (string) $status);
@endphp

<span {{ $attributes->merge(['class' => "badge-pill {$class}"]) }}>{{ $label }}</span>
