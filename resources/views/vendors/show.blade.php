{{--
  Vendor Show Page — vendors.show
  Feature preservation notes:
    - Breadcrumb back link to vendors index
    - Header card with vendor name, contact_name, active/inactive badge
    - Edit vendor link
    - New Document link (vendor-documents.create with vendor_id query)
    - Details dl (phone, email, full address, account_number, payment_terms, default expense account) all conditional
    - Notes section (conditional)
    - Recent Documents card with View all link and table (date, type badge, total, status via partial, view link)
  Layout changes only:
    - Outer container widened from max-w-3xl to max-w-7xl
    - Vertical spacing tightened from space-y-6 to space-y-4
    - All Alpine state, forms, routes, and PHP logic kept intact
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Breadcrumb --}}
    <a href="{{ route('vendors.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        All Vendors
    </a>

    {{-- Header --}}
    <div class="surface-1 p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $vendor->name }}</h1>
                @if ($vendor->contact_name)
                    <p class="text-sm text-gray-500 mt-1">{{ $vendor->contact_name }}</p>
                @endif
                <div class="mt-2">
                    @if ($vendor->is_active)
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-white/5 text-gray-500">Inactive</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('vendors.edit', $vendor) }}"
                   class="inline-flex items-center px-4 py-2 bg-white/5 border border-white/10 text-gray-300 text-sm font-medium rounded-md hover:bg-white/5 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                    Edit
                </a>
                <a href="{{ route('vendor-documents.create', ['vendor_id' => $vendor->id]) }}"
                   class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-md  transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Document
                </a>
            </div>
        </div>
    </div>

    {{-- Contact & Address --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-4">Details</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            @if ($vendor->phone)
            <div>
                <dt class="text-gray-500">Phone</dt>
                <dd class="font-medium text-white mt-0.5">{{ $vendor->phone }}</dd>
            </div>
            @endif
            @if ($vendor->email)
            <div>
                <dt class="text-gray-500">Email</dt>
                <dd class="font-medium text-white mt-0.5">{{ $vendor->email }}</dd>
            </div>
            @endif
            @if ($vendor->fullAddress())
            <div class="sm:col-span-2">
                <dt class="text-gray-500">Address</dt>
                <dd class="font-medium text-white mt-0.5">{{ $vendor->fullAddress() }}</dd>
            </div>
            @endif
            @if ($vendor->account_number)
            <div>
                <dt class="text-gray-500">Account Number</dt>
                <dd class="font-medium text-white mt-0.5 font-mono">{{ $vendor->account_number }}</dd>
            </div>
            @endif
            @if ($vendor->payment_terms)
            <div>
                <dt class="text-gray-500">Payment Terms</dt>
                <dd class="font-medium text-white mt-0.5">{{ $vendor->payment_terms }}</dd>
            </div>
            @endif
            @if ($vendor->defaultExpenseAccount)
            <div>
                <dt class="text-gray-500">Default Expense Account</dt>
                <dd class="font-medium text-white mt-0.5">{{ $vendor->defaultExpenseAccount->code }} – {{ $vendor->defaultExpenseAccount->name }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Notes --}}
    @if ($vendor->notes)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-2">Notes</h2>
        <p class="text-sm text-gray-300 whitespace-pre-line">{{ $vendor->notes }}</p>
    </div>
    @endif

    {{-- Recent Documents --}}
    <div class="surface-1 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-300">Recent Documents</h2>
            <a href="{{ route('vendor-documents.index', ['vendor_id' => $vendor->id]) }}"
               class="text-sm text-cyan-400 hover:text-cyan-300 font-medium">View all</a>
        </div>

        @if ($vendor->documents->isEmpty())
            <p class="text-sm text-gray-500">No documents yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full divide-y divide-white/5 text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-3 py-2"><span class="sr-only">View</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($vendor->documents as $doc)
                            <tr class="hover:bg-white/5">
                                <td class="px-3 py-2 text-gray-400">{{ $doc->document_date->format('M j, Y') }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $doc->isReceipt() ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-cyan-400' }}">
                                        {{ $doc->typeLabel() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right font-medium">${{ number_format($doc->total, 2) }}</td>
                                <td class="px-3 py-2">
                                    @include('vendor-documents._status-badge', ['status' => $doc->status])
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('vendor-documents.show', $doc) }}"
                                       class="text-cyan-400 hover:text-cyan-300 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
