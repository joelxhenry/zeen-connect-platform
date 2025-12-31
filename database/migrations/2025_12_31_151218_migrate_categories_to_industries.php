<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Copy all existing categories to industries
        $categories = DB::table('categories')->get();

        foreach ($categories as $category) {
            DB::table('industries')->insert([
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ]);
        }

        // Step 2: For each provider, find their most used category and assign as industry
        $providers = DB::table('providers')->get();

        foreach ($providers as $provider) {
            // Get the most common category_id from their services
            $mostUsedCategory = DB::table('services')
                ->select('category_id', DB::raw('COUNT(*) as count'))
                ->where('provider_id', $provider->id)
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->orderByDesc('count')
                ->first();

            if ($mostUsedCategory && $mostUsedCategory->category_id) {
                DB::table('providers')
                    ->where('id', $provider->id)
                    ->update(['industry_id' => $mostUsedCategory->category_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear industry_id from providers
        DB::table('providers')->update(['industry_id' => null]);

        // Delete all industries
        DB::table('industries')->truncate();
    }
};
