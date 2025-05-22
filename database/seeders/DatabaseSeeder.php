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
        $role4 = Role::create(['name' => 'vendor']);
        $role5 = Role::create(['name' => 'customer']);

        $user = User::create([
            'first_name' => 'Aiwa',
            'last_name' => 'Admin',
            'email' => 'info@aiwagroup.org',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role1);
        
    }
}
