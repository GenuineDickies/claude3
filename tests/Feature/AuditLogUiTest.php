<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditLogUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_page_lists_entries(): void
    {
        $this->withoutVite();

        $user = User::factory()->create(['name' => 'Admin User']);

        AuditLog::create([
            'user_id' => $user->id,
            'event' => 'login_success',
            'details' => ['guard' => 'web'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Audit Logs');
        $response->assertSee('login_success');
        $response->assertSee('127.0.0.1');
        $response->assertSee('Admin User');
    }
}