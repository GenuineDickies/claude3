<?php

use App\Models\Role;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::firstOrCreate(
            ['role_name' => 'Technician'],
            ['description' => 'Field technician who can be assigned to service requests'],
        );

        // Create TechnicianProfile for any existing users that already have this role
        User::whereHas('roles', fn ($q) => $q->where('roles.id', $role->id))
            ->whereDoesntHave('technicianProfile')
            ->each(fn (User $u) => TechnicianProfile::create(['user_id' => $u->id]));
    }

    public function down(): void
    {
        Role::where('role_name', 'Technician')->delete();
    }
};
