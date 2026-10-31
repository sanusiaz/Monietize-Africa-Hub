<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Creates (or updates) the administrator from ADMIN_NAME, ADMIN_EMAIL and ADMIN_PASSWORD in .env.
 * Safe to run again: it never creates a second account for the same email. Running it after changing
 * ADMIN_PASSWORD in .env is the way to reset the admin password.
 */
class AdminSeeder extends Seeder
{
    /** The placeholder that ships in the example env files; it must never reach a live site. */
    private const PLACEHOLDERS = ['ChangeMe!2026', 'password', 'changeme', 'admin'];

    public function run(): void
    {
        $input = [
            'name' => config('admin.name'),
            'email' => config('admin.email'),
            'password' => config('admin.password'),
        ];

        $production = app()->isProduction();

        $validator = Validator::make($input, [
            'name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:190',
            'password' => ['required', 'string', 'max:72', $production ? 'min:12' : 'min:8'],
        ], [
            'email.required' => 'Set ADMIN_EMAIL in your .env file.',
            'password.required' => 'Set ADMIN_PASSWORD in your .env file.',
            'password.min' => 'ADMIN_PASSWORD is too short (use at least :min characters).',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException('Admin account not created. '.implode(' ', $validator->errors()->all()));
        }

        if ($production && in_array($input['password'], self::PLACEHOLDERS, true)) {
            throw new InvalidArgumentException('Admin account not created. ADMIN_PASSWORD is still a placeholder; choose your own strong password.');
        }

        $admin = User::updateOrCreate(
            ['email' => mb_strtolower(trim($input['email']))],
            [
                'name' => $input['name'],
                'password' => $input['password'],
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $this->command?->info("Administrator ready: {$admin->email}. Remove ADMIN_PASSWORD from .env now.");
    }
}
