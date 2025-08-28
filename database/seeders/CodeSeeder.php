<?php

namespace Database\Seeders;

use App\Models\Code;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Code::create([
                'user_id' => User::first()->id,
                'product_id' => $i,
                "code" => "asdas-adas-dasd",
                'is_used' => false
            ]);
        }
    }
}
