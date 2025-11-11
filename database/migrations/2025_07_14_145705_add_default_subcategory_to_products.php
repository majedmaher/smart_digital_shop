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
        // Ensure we have a default subcategory
        $defaultSubCategoryId = DB::table('sub_categories')
            ->where('name->en', 'General')
            ->value('id');

        if (!$defaultSubCategoryId) {
            $categoryId = DB::table('categories')->value('id');
            $userId = DB::table('users')->value('id');

            // Create default category if it doesn't exist
            if (!$categoryId) {
                $categoryId = DB::table('categories')->insertGetId([
                    'user_id' => $userId, // null if no users exist
                    'name' => json_encode([
                        'en' => 'General',
                        'ar' => 'عام'
                    ]),
                    'icon' => 'fas fa-box',
                    'image' => 'default-category.png',
                    'slug' => json_encode([
                        'en' => 'general',
                        'ar' => 'عام'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Only create subcategory if user exists (sub_categories.user_id is not nullable)
            if ($userId) {
                $defaultSubCategoryId = DB::table('sub_categories')->insertGetId([
                    'user_id' => $userId,
                    'category_id' => $categoryId,
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
            }
        }

        // Update products that don't have a subcategory (only if defaultSubCategoryId exists)
        if ($defaultSubCategoryId) {
            DB::table('products')
                ->whereNull('sub_category_id')
                ->update(['sub_category_id' => $defaultSubCategoryId]);

            // Make sub_category_id nullable to prevent future issues
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('sub_category_id')->nullable()->change();
            });

            // Add a default value constraint
            Schema::table('products', function (Blueprint $table) use ($defaultSubCategoryId) {
                $table->foreignId('sub_category_id')->default($defaultSubCategoryId)->change();
            });
        } else {
            // If no default subcategory, just make sub_category_id nullable
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('sub_category_id')->nullable()->change();
            });
        }
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

