<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use App\RoleEnum;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $user = User::factory()->create([
        //     'name' => 'Wassim',
        //     'email' => 'wasimabdelhadi78@gmail.com',
        //     // 'name' => 'Test User',
        //     // 'email' => 'test@test.com',
        //     'password' => 'asdasd123123',
        // ]);

        $user = User::first();
        $this->call([
            RolesAndPermissionsSeeder::class,
            // CategorySeeder::class,
            // SubCategorySeeder::class,
            // ProductSeeder::class,
            // CodeSeeder::class,
        ]);
        $user->assignRole(RoleEnum::ADMIN);
        // User::factory(10)->create();

    }
}
