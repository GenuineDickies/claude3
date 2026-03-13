@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to users</a>
        <h1 class="mt-2 text-2xl font-bold text-white">Edit {{ $managedUser->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Update identity fields, roles, and account status.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="surface-1 p-6">
        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300" for="name">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $managedUser->name) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300" for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username', $managedUser->username) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $managedUser->email) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300" for="status">Status</label>
                    <select id="status" name="status" class="select-crystal w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                        <option value="active" @selected(old('status', $managedUser->status) === 'active')>Active</option>
                        <option value="disabled" @selected(old('status', $managedUser->status) === 'disabled')>Disabled</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300" for="password">New password</label>
                    <input id="password" type="password" name="password" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                    <p class="mt-1 text-xs text-gray-500">Leave blank to keep the existing password.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300" for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Roles</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-3 rounded-lg border border-white/10 p-3 hover:border-blue-300 hover:bg-cyan-500/10/40">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $managedUser->roles->pluck('id')->all()), true)) class="mt-1 rounded border-white/10 text-cyan-400 focus:ring-cyan-500">
                            <span>
                                <span class="block text-sm font-medium text-white">{{ $role->role_name }}</span>
                                <span class="block text-xs text-gray-500">{{ $role->description ?: 'No description provided.' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Cancel</a>
                <button type="submit" class="btn-crystal px-4 py-2 text-sm font-semibold">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endsection