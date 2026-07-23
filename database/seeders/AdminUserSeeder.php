<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@cegu.test');
        $password = env('ADMIN_PASSWORD', 'password');

        // PENGAMAN RILIS: di production, kredensial default DILARANG.
        // Bila ADMIN_PASSWORD tidak diset, buat password acak dan tampilkan
        // sekali di output seeder — jangan pernah biarkan "password" hidup
        // di server publik.
        if (app()->environment('production') && in_array($password, ['password', 'ubah-password-ini'], true)) {
            $password = \Illuminate\Support\Str::password(16, symbols: false);
            $this->command->warn('ADMIN_PASSWORD tidak diset — password acak dibuat. CATAT SEKARANG (hanya tampil sekali):');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'CEGU Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Admin: {$email} / {$password}");
    }
}
