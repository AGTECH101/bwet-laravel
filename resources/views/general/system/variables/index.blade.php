@extends('layouts.app')

@section('title', 'System Variables - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">System Variables</h1>
        <p class="text-sm text-gray-600">Configure system-wide settings and thresholds</p>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Configuration Variables</h3>
                <span class="text-sm text-gray-500">{{ $variables->count() }} variables</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variable</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($variables as $var)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $var->name }}</div>
                            <div class="text-xs text-gray-500">{{ $var->key }}</div>
                            @if($var->description)<div class="text-xs text-gray-400">{{ Str::limit($var->description, 80) }}</div>@endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                {{ $var->category == 'financial' ? 'bg-green-100 text-green-800' :
                                   ($var->category == 'performance' ? 'bg-blue-100 text-blue-800' :
                                   ($var->category == 'weighing' ? 'bg-yellow-100 text-yellow-800' :
                                   ($var->category == 'slaughter' ? 'bg-red-100 text-red-800' :
                                   'bg-gray-100 text-gray-800'))) }}">
                                {{ ucfirst($var->category) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono text-gray-900">{{ $var->value }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($var->data_type) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($var->is_active)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i> Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('system.variables.edit', $var) }}" class="text-primary-600 hover:text-primary-900">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No system variables found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Categories Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Financial Settings</h3>
            <div class="space-y-2">
                @foreach($variables->where('category', 'financial') as $var)
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">{{ $var->name }}</span>
                    <span class="text-sm font-mono text-gray-900">{{ $var->value }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Performance Settings</h3>
            <div class="space-y-2">
                @foreach($variables->where('category', 'performance') as $var)
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">{{ $var->name }}</span>
                    <span class="text-sm font-mono text-gray-900">{{ $var->value }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-blue-50 rounded-xl border border-blue-200 p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0"><i class="fas fa-info-circle text-blue-400 text-xl"></i></div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-blue-800">About System Variables</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Changes affect financial calculations, performance thresholds, weighing schedules, and slaughter triggers.</p>
                    <p class="mt-1">Use caution when modifying these values as they directly impact business decisions.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection