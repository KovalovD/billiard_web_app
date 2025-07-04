<?php

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Boot the trait
     */
    public static function bootHasSlug(): void
    {
        static::creating(static function (Model $model) {
            if (empty($model->slug)) {
                $model->slug = $model->generateSlug();
            }
        });

        static::updating(static function (Model $model) {
            $slugField = $model->getSlugSourceField();

            // Only regenerate slug if the source field has changed
            if ($model->isDirty($slugField) && $model->shouldRegenerateSlugOnUpdate()) {
                $model->slug = $model->generateSlug();
            }
        });
    }

    /**
     * Generate a unique slug for the model
     */
    public function generateSlug(): string
    {
        $slugSource = $this->getSlugSource();
        $slug = Str::slug($slugSource);
        $slug = Str::replace('pul', 'pool', $slug);
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }

    /**
     * Get the source for generating the slug
     */
    protected function getSlugSource(): string
    {
        $field = $this->getSlugSourceField();

        if ($field === 'full_name' && method_exists($this, 'getFullNameAttribute')) {
            return $this->full_name;
        }

        return $this->{$field} ?? '';
    }

    /**
     * Get the field name to use for generating slug
     */
    protected function getSlugSourceField(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'name';
    }

    /**
     * Check if slug already exists
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);

        // If updating, exclude current model
        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    /**
     * Determine if slug should be regenerated on update
     */
    protected function shouldRegenerateSlugOnUpdate(): bool
    {
        return property_exists($this, 'regenerateSlugOnUpdate') ? $this->regenerateSlugOnUpdate : false;
    }

    /**
     * Find a model by slug or fail
     */
    public static function findBySlugOrFail(string $slug): self
    {
        return static::where('slug', $slug)->firstOrFail();
    }

    /**
     * Find a model by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
