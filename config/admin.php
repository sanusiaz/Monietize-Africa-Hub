<?php

/*
 * First administrator account, created or updated by `php artisan db:seed --class=AdminSeeder`.
 * Set these in .env; remove ADMIN_PASSWORD from .env once the account exists.
 */
return [
    'name' => env('ADMIN_NAME', 'Administrator'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];
