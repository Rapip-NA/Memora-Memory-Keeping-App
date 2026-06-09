<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@memora.id'],
            [
                'name'     => 'Admin Memora',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'status'   => 'active',
                'city'     => 'Jakarta',
                'job'      => 'Administrator',
            ]
        );

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

        // Seed default classrooms
        $classrooms = [
            ['name' => 'XII RPL 1', 'description' => 'Rekayasa Perangkat Lunak 1'],
            ['name' => 'XII RPL 2', 'description' => 'Rekayasa Perangkat Lunak 2'],
            ['name' => 'XII TKJ 1', 'description' => 'Teknik Komputer & Jaringan 1'],
            ['name' => 'XII TKJ 2', 'description' => 'Teknik Komputer & Jaringan 2'],
            ['name' => 'XII MM', 'description' => 'Multimedia'],
        ];

        foreach ($classrooms as $classroom) {
            \App\Models\Classroom::updateOrCreate(
                ['name' => $classroom['name']],
                ['description' => $classroom['description']]
            );
        }
    }
}
