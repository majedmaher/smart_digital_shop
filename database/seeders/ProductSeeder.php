<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Product::create([
                'user_id' => 1,
                'title' => [
                    'en' => 'test',
                    'ar' => 'تست'
                ],
                'content' => [
                    'en' => 'content',
                    'ar' => 'محتوى'
                ],
                'description' => [
                    'en' => 'description',
                    'ar' => 'وصف'
                ],
                'image' => 'uploads/categories/all images has same size6880a6cfd6e0b.png',
                'category_id' => $i,
                'sub_category_id' => $i,
                'price' => 100,
                'price_before' => 120,
                'shipping_payment' => 'code',
                'is_active' => 1
            ]);
        }
    }
}
