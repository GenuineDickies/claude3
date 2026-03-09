<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('status', 20)->default('active')->after('email_verified_at');
            $table->index('status');
            $table->unique('username');
        });

        DB::table('users')->orderBy('id')->get(['id', 'name', 'email'])->each(function (object $user): void {
            $base = Str::of($user->name ?: Str::before($user->email, '@'))
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '.')
                ->trim('.')
                ->value();

            if ($base === '') {
                $base = 'user';
            }

            $candidate = $base;
            $suffix = 1;

            while (DB::table('users')
                ->where('id', '!=', $user->id)
                ->where('username', $candidate)
                ->exists()) {
                $suffix++;
                $candidate = $base.'.'.$suffix;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'username' => $candidate,
                    'status' => 'active',
                ]);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_name');
            $table->string('page_path')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('page_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'page_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 100);
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
        });

        $administratorRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Administrator',
            'description' => 'Full access to all registered pages and administration tools.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminUserId = DB::table('users')
            ->where('email', 'test@example.com')
            ->value('id');

        if ($adminUserId !== null) {
            DB::table('role_user')->insertOrIgnore([
                'user_id' => $adminUserId,
                'role_id' => $administratorRoleId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('page_role');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['status']);
            $table->dropColumn(['username', 'status']);
        });
    }
};