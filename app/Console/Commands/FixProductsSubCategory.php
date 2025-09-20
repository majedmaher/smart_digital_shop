<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixProductsSubCategory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:fix-subcategory {--force : Force update without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix products that don\'t have a subcategory by assigning them to a default subcategory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Starting products subcategory fix...');

        // Check if there are products without subcategory
        $productsWithoutSubCategory = Product::whereNull('sub_category_id')->count();
        
        if ($productsWithoutSubCategory === 0) {
            $this->info('✅ All products already have a subcategory assigned.');
            return 0;
        }

        $this->warn("⚠️  Found {$productsWithoutSubCategory} products without subcategory.");

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to assign them to a default subcategory?')) {
                $this->info('❌ Operation cancelled.');
                return 0;
            }
        }

        // Create or get default subcategory
        $defaultSubCategory = $this->createDefaultSubCategory();
        
        if (!$defaultSubCategory) {
            $this->error('❌ Failed to create default subcategory.');
            return 1;
        }

        $this->info("📁 Using default subcategory: {$defaultSubCategory->name['en']} (ID: {$defaultSubCategory->id})");

        // Update products
        $updatedCount = Product::whereNull('sub_category_id')
            ->update(['sub_category_id' => $defaultSubCategory->id]);

        $this->info("✅ Successfully updated {$updatedCount} products.");

        // Show statistics
        $this->showStatistics();

        return 0;
    }

    /**
     * Create default subcategory if it doesn't exist
     */
    private function createDefaultSubCategory(): ?SubCategory
    {
        $defaultSubCategory = SubCategory::where('name->en', 'General')->first();
        
        if ($defaultSubCategory) {
            return $defaultSubCategory;
        }

        $this->info('📁 Creating default subcategory...');

        // Get first category or create one
        $category = Category::first();
        if (!$category) {
            $this->error('❌ No categories found. Please create a category first.');
            return null;
        }

        try {
            $defaultSubCategory = SubCategory::create([
                'user_id' => 1, // Admin user ID
                'category_id' => $category->id,
                'parent_id' => null,
                'name' => [
                    'en' => 'General',
                    'ar' => 'عام'
                ],
                'icon' => 'fas fa-box',
                'image' => 'default-subcategory.png',
                'slug' => [
                    'en' => 'general',
                    'ar' => 'عام'
                ],
            ]);

            $this->info("✅ Created default subcategory: {$defaultSubCategory->name['en']}");
            return $defaultSubCategory;
        } catch (\Exception $e) {
            $this->error("❌ Failed to create default subcategory: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Show products statistics
     */
    private function showStatistics(): void
    {
        $this->info('📊 Products Statistics:');
        
        $totalProducts = Product::count();
        $productsWithSubCategory = Product::whereNotNull('sub_category_id')->count();
        $productsWithoutSubCategory = Product::whereNull('sub_category_id')->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Products', $totalProducts],
                ['With Subcategory', $productsWithSubCategory],
                ['Without Subcategory', $productsWithoutSubCategory],
            ]
        );

        // Show subcategory distribution
        $subcategoryDistribution = Product::select('sub_category_id')
            ->with('subCategory:id,name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('sub_category_id')
            ->get();

        if ($subcategoryDistribution->count() > 0) {
            $this->info('📈 Subcategory Distribution:');
            $distributionData = $subcategoryDistribution->map(function ($item) {
                return [
                    'Subcategory' => $item->subCategory ? $item->subCategory->name['en'] : 'Unknown',
                    'Count' => $item->count
                ];
            })->toArray();

            $this->table(['Subcategory', 'Count'], $distributionData);
        }
    }
}
