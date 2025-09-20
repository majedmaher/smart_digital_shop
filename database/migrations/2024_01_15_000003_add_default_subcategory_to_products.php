<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create a default subcategory
        $defaultSubCategoryId = DB::table('sub_categories')->insertGetId([
            'user_id' => 1, // Assuming admin user ID is 1
            'category_id' => 1, // Assuming first category ID is 1
            'parent_id' => null,
            'name' => json_encode([
                'en' => 'General',
                'ar' => 'عام'
            ]),
            'icon' => 'fas fa-box',
            'image' => 'default-subcategory.png',
            'slug' => json_encode([
                'en' => 'general',
                'ar' => 'عام'
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update products that don't have a subcategory
        DB::table('products')
            ->whereNull('sub_category_id')
            ->update(['sub_category_id' => $defaultSubCategoryId]);

        // Make sub_category_id nullable to prevent future issues
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('sub_category_id')->nullable()->change();
        });

        // Add a default value constraint
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('sub_category_id')->default($defaultSubCategoryId)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the default constraint
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('sub_category_id')->nullable(false)->change();
        });

        // Delete the default subcategory
        DB::table('sub_categories')
            ->where('name->en', 'General')
            ->delete();
    }
};
