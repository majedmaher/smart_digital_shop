<?php

namespace Database\Seeders;

use App\Models\SubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            SubCategory::create([
                'user_id' => 1,
                'name' => [
                    'en' => 'test',
                    'ar' => 'تست'
                ],
                'icon' => 'uploads/categories/all images has same size6880a6cfd6e0b.png',
                'image' => 'uploads/categories/all images has same size6880a6cfd6e0b.png',
                'category_id' => $i,
            ]);
        }
    }
}
