{{--
  Technician Compliance — technician-profiles.index
  Controller vars: $users
  Features preserved:
    - Success flash message
    - Table cols: Technician, License, Insurance, Background, Drug Screen, Actions (View / Edit)
    - Compliance badges via x-compliance-badge component
    - Empty state row
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">Technician Compliance</h1>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-500/10 p-4">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    <div class="surface-1 overflow-hidden">
        <table class="table-crystal min-w-full text-sm">
            <thead>
                <tr class="border-b bg-white/5 text-left text-gray-500">
                    <th class="px-4 py-3 font-medium">Technician</th>
                    <th class="px-4 py-3 font-medium">License</th>
                    <th class="px-4 py-3 font-medium">Insurance</th>
                    <th class="px-4 py-3 font-medium">Background</th>
                    <th class="px-4 py-3 font-medium">Drug Screen</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php $p = $user->technicianProfile; @endphp
                    <tr class="border-b last:border-0 hover:bg-white/5">
                        <td class="px-4 py-3 font-medium text-white">{{ $user->name }}</td>
                        <td class="px-4 py-3">
                            @if($p)
                                <x-compliance-badge :status="$p->licenseStatus()" />
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($p)
                                <x-compliance-badge :status="$p->insuranceStatus()" />
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($p)
                                <x-compliance-badge :status="$p->background_check_status" />
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($p)
                                <x-compliance-badge :status="$p->drug_screen_status" />
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($p)
                                <a href="{{ route('technician-profiles.show', $user) }}" class="text-cyan-400 hover:text-cyan-300 text-sm font-medium">View</a>
                            @endif
                            <a href="{{ route('technician-profiles.edit', $user) }}" class="text-cyan-400 hover:text-cyan-300 text-sm font-medium ml-3">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No technicians found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
