<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Access\AccessControlService;
use App\Services\Access\PageRegistryService;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $accessControl = app(AccessControlService::class);
        $pageRegistry = app(PageRegistryService::class);

        $pageRegistry->sync();

        $administratorRole = $accessControl->administratorRole();

        User::query()
            ->where('email', 'test@example.com')
            ->first()?->roles()->syncWithoutDetaching([$administratorRole->id]);
    }
}