@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<x-page-header title="Suppliers" lede="Simple supplier directory used when submitting invoices.">
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary">New supplier</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="panel">
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead><tr><th>Name</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td class="fw-semibold">{{ $supplier->name }}</td>
                    <td>
                        @if($supplier->is_active)
                            <span class="badge-pill badge-final">Active</span>
                        @else
                            <span class="badge-pill badge-archived">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
                            <a href="{{ route('suppliers.edit', $supplier) }}">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No suppliers yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $suppliers->links() }}</div>
@endsection
