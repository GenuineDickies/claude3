@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to users</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Create User</h1>
    </div>

    <div class="rounded-lg bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Full name</label>
                    <input id="name" name="name" value="{{ old('name') }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="status">Status</label>
                    <select id="status" name="status" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="disabled" @selected(old('status') === 'disabled')>Disabled</option>
                    </select>
                    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="password">Password</label>
                    <input id="password" type="password" name="password" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Roles</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:border-blue-300 hover:bg-blue-50/40">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', []), true)) class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-medium text-gray-900">{{ $role->role_name }}</span>
                                <span class="block text-xs text-gray-500">{{ $role->description ?: 'No description provided.' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('role_ids')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Create user</button>
            </div>
        </form>
    </div>
</div>
@endsection