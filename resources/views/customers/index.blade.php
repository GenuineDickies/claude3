{{--
  Customers — customers.index
  Controller vars: $customers (paginator), $currentSearch, $currentConsent, $currentActive
  Features preserved:
    - New Service Request button (links to service-requests.create)
    - Filters: search (name/phone), SMS Consent (yes/no), Status (active/inactive)
    - Clear filters link (conditional)
    - Empty state with icon
    - Table cols: Name, Phone, SMS badge, Status badge, Service Requests count, Added, view + service requests actions
    - Pagination with withQueryString
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Customers</h1>
        <a href="{{ route('service-requests.create') }}"
           class="inline-flex items-center px-4 py-2 btn-crystal text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Service Request
        </a>
    </div>

    {{-- Filters --}}
    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ $currentSearch }}"
                       placeholder="Name or phone number…"
                       class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>

            <div>
                <label for="consent" class="block text-xs font-medium text-gray-500 mb-1">SMS Consent</label>
                <select name="consent" id="consent"
                        class="rounded-md border-white/10 text-sm shadow-sm input-crystal">
                    <option value="">All</option>
                    <option value="yes" @selected($currentConsent === 'yes')>Opted In</option>
                    <option value="no" @selected($currentConsent === 'no')>Opted Out</option>
                </select>
            </div>

            <div>
                <label for="active" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="active" id="active"
                        class="rounded-md border-white/10 text-sm shadow-sm input-crystal">
                    <option value="">All</option>
                    <option value="1" @selected($currentActive === '1')>Active</option>
                    <option value="0" @selected($currentActive === '0')>Inactive</option>
                </select>
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 btn-crystal text-sm">
                Filter
            </button>

            @if ($currentSearch || $currentConsent || $currentActive !== null && $currentActive !== '')
                <a href="{{ route('customers.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-300 underline">Clear</a>
            @endif
        </form>
    </div>

    {{-- Results --}}
    <div class="surface-1">
        @if ($customers->isEmpty())
            <div class="p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No customers found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Requests</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-white/5">
                        @foreach ($customers as $customer)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-3 text-sm font-medium text-white">
                                    {{ $customer->first_name }} {{ $customer->last_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400 font-mono">
                                    {{ $customer->phone }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($customer->hasSmsConsent())
                                        <span class="inline-flex items-center rounded-full bg-green-500/10 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset">
                                            Opted In
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-white/5 px-2 py-1 text-xs font-medium text-gray-400 ring-1 ring-gray-500/10 ring-inset">
                                            No Consent
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($customer->is_active)
                                        <span class="inline-flex items-center rounded-full bg-cyan-500/10 px-2 py-1 text-xs font-medium text-cyan-400 ring-1 ring-blue-700/10 ring-inset">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/10 ring-inset">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">
                                    {{ $customer->service_requests_count }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $customer->created_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('customers.show', $customer) }}"
                                           class="text-gray-400 hover:text-cyan-400" title="View customer">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                        </a>
                                        <a href="{{ route('service-requests.index', ['search' => $customer->phone]) }}"
                                           class="text-gray-400 hover:text-cyan-400" title="View service requests">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="px-4 py-3 border-t border-white/10">
                    {{ $customers->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
