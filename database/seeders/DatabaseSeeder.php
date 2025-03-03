<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $role1 = Role::create(['name' => 'super-admin']);
        $role2 = Role::create(['name' => 'admin']);
        $role3 = Role::create(['name' => 'manager']);
        $role4 = Role::create(['name' => 'customer']);
        User::find(1)->assignRole($role1);
    }
}
