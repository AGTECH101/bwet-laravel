@extends('layouts.app')

@section('title', $sector->name . ' Dashboard - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $sector->name }} Dashboard</h1>
        <p class="text-sm text-gray-600">Welcome back, {{ auth()->user()->name }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
    <div class="w-24 h-24 mx-auto rounded-full bg-primary-100 flex items-center justify-center mb-4">
        <i class="fas fa-{{ $sector->icon ?? 'building' }} text-primary-600 text-4xl"></i>
    </div>
    <h2 class="text-2xl font-bold text-gray-900">{{ $sector->name }} Sector</h2>
    <p class="text-gray-600 mt-2">{{ $message ?? 'This sector is currently under development.' }}</p>
    <p class="text-sm text-gray-500 mt-4">You can still access common features from the sidebar.</p>
</div>
@endsection