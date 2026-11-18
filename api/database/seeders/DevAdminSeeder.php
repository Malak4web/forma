<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local-only admin account. Credentials live in .env (DEV_ADMIN_PHONE / DEV_ADMIN_PASSWORD),
 * never in code. Refuses to run outside the local environment.
 *   php artisan db:seed --class=DevAdminSeeder
 */
class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->error('DevAdminSeeder runs in local only.');

            return;
        }

        $phone = env('DEV_ADMIN_PHONE');
        $password = env('DEV_ADMIN_PASSWORD');
        if (! $phone || ! $password) {
            $this->command?->error('Set DEV_ADMIN_PHONE and DEV_ADMIN_PASSWORD in .env first.');

            return;
        }

        $user = User::withTrashed()->firstOrNew(['phone' => $phone]);
        $user->fill(['name' => $user->name ?: 'أدمن فورمة', 'password' => $password]);
        $user->is_admin = true;
        $user->deleted_at = null;
        $user->save();

        $this->command?->info("Local admin ready: {$phone} (password from .env)");
    }
}
