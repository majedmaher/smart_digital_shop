<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Category::create([
                'user_id' => 1,
                'name' => [
                    'en' => 'test',
                    'ar' => 'تست'
                ],
                'icon' => 'uploads/categories/grid6880a6cfd6763.jpg',
                'image' => 'uploads/categories/all images has same size6880a6cfd6e0b.png',
            ]);
        }
    }
}
