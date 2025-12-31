<?php

namespace App\Domains\Service\Traits;

use App\Domains\Service\Models\Category;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCategories
{
    /**
     * Get all categories assigned to this model.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Attach a category to this model.
     */
    public function attachCategory(Category $category): void
    {
        if (! $this->hasCategory($category->id)) {
            $this->categories()->attach($category->id);
        }
    }

    /**
     * Detach a category from this model.
     */
    public function detachCategory(int $categoryId): void
    {
        $this->categories()->detach($categoryId);
    }

    /**
     * Sync categories for this model.
     * Replaces all existing categories with the given array.
     */
    public function syncCategories(array $categoryIds): void
    {
        $this->categories()->sync($categoryIds);
    }

    /**
     * Check if this model has a specific category.
     */
    public function hasCategory(int $categoryId): bool
    {
        return $this->categories()->where('categories.id', $categoryId)->exists();
    }

    /**
     * Check if this model has any categories.
     */
    public function hasCategories(): bool
    {
        return $this->categories()->exists();
    }

    /**
     * Get all category names as an array.
     */
    public function getCategoryNames(): array
    {
        return $this->categories->pluck('name')->toArray();
    }

    /**
     * Get the first category (for display purposes).
     */
    public function getPrimaryCategory(): ?Category
    {
        return $this->categories->first();
    }

    /**
     * Get category IDs as an array.
     */
    public function getCategoryIds(): array
    {
        return $this->categories->pluck('id')->toArray();
    }

    /**
     * Get categories count.
     */
    public function getCategoriesCount(): int
    {
        return $this->categories()->count();
    }
}
