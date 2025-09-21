<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;


class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
   public function run()
    {
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $manager = User::create([
            'name' => 'Manager One',
            'email' => 'manager@example.test',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);
        $manager->assignRole($managerRole);

        $user = User::create([
            'name' => 'User One',
            'email' => 'user@example.test',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
        $user->assignRole($userRole);
    }
}
