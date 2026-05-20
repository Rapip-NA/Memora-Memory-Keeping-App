<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin
        User::updateOrCreate(
            ['email' => 'admin@lifeafter.test'],
            [
                'name'     => 'Admin Angkatan',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'status'   => 'active',
                'city'     => 'Jakarta',
                'job'      => 'Pengurus Angkatan',
            ]
        );

        // 2. Member dummy
        User::updateOrCreate(
            ['email' => 'member@lifeafter.test'],
            [
                'name'       => 'Rafif Dummy',
                'password'   => Hash::make('password'),
                'role'       => 'member',
                'status'     => 'active',
                'city'       => 'Bandung',
                'job'        => 'Software Engineer',
                'company'    => 'Tech Corp',
                'born_date'  => '2000-05-15',
                'lat'        => -6.9175,
                'lng'        => 107.6191,
            ]
        );
    }
}
