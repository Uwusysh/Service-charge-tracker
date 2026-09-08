@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <div class="brand-mark">SetK</div>
        <p class="brand-sub">Annual service charge budget &amp; spend tracker</p>

        <form method="POST" action="{{ route('login.demo') }}" class="mb-3">
            @csrf
            <button class="btn btn-primary btn-lg w-100" type="submit">
                Enter demo
            </button>
        </form>
        <p class="text-center text-muted small mb-0">
            Opens the Block Manager workspace with sample Maple Court data.
        </p>
    </div>
</div>
@endsection
