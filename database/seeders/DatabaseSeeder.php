<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Seo;
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
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'support@chargerspeed.online',
            // 'name' => 'Test User',
            // 'email' => 'test@test.com',
            'password' => 'As$#708090',
        ]);

        // $user = User::first();
        // $this->call([
        //     RolesAndPermissionsSeeder::class,
        // CategorySeeder::class,
        // SubCategorySeeder::class,
        // ProductSeeder::class,
        // CodeSeeder::class,
        // ]);
        $user->assignRole(RoleEnum::ADMIN);
        // User::factory(10)->create();

        // Seo::create([
        //     'title' => [
        //         'ar' => 'إنجوي قيمز',
        //         'en' => 'EnjoyGames'
        //     ],
        //     'description' => [
        //         'ar' => 'وصف',
        //         'en' => 'description'
        //     ],
        //     'keywords' => [
        //         'ar' => 'موقع,اكواد',
        //         'en' => 'website,code'
        //     ]
        // ]);
    }
}
