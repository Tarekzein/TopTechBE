<?php

namespace Modules\Blog\App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Modules\Common\Services\CloudImageService;

class Post extends Model
{
    use HasFactory, SoftDeletes;
    use Sluggable;
    
    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_public_id',
        'category_id',
        'author_id',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'is_featured',
        'view_count'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'view_count' => 'integer'
    ];

    protected $appends = ['featured_image_url'];

    /**
     * Event handlers for the model
     */
    protected static function boot()
    {
        parent::boot();

        // Delete featured image from Cloudinary when post is deleted
        static::deleted(function ($post) {
            if ($post->featured_image_public_id) {
                $cloudinaryService = app(CloudImageService::class);
                $cloudinaryService->deleteImage($post->featured_image_public_id);
            }
        });

        // Delete old featured image from Cloudinary when updating
        static::updating(function ($post) {
            $original = $post->getOriginal();
            if ($post->isDirty('featured_image_public_id') && $original['featured_image_public_id']) {
                $cloudinaryService = app(CloudImageService::class);
                $cloudinaryService->deleteImage($original['featured_image_public_id']);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'blog_post_tag', 'post_id', 'tag_id')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id')
            ->where('status', 'approved');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeWithCategory($query)
    {
        return $query->with('category');
    }

    public function scopeWithAuthor($query)
    {
        return $query->with('author');
    }

    public function scopeWithTags($query)
    {
        return $query->with('tags');
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    /**
     * Get the featured image URL with transformations
     */
    public function getFeaturedImageUrlAttribute()
    {
        if (!$this->featured_image_public_id) {
            return $this->featured_image;
        }

        try {
            $cloudinaryService = app(CloudImageService::class);
            return $cloudinaryService->getImageUrl($this->featured_image_public_id, [
                'width' => 800,
                'height' => 400,
                'crop' => 'fill',
                'quality' => 'auto'
            ]);
        } catch (\Exception $e) {
            return $this->featured_image;
        }
    }

    /**
     * Get the original featured image URL
     */
    public function getOriginalFeaturedImageUrlAttribute()
    {
        return $this->featured_image;
    }

    /**
     * Upload featured image to Cloudinary
     */
    public function uploadFeaturedImage($imageFile): ?array
    {
        try {
            $cloudinaryService = app(CloudImageService::class);
            
            return $cloudinaryService->uploadImage($imageFile->getRealPath(), [
                'folder' => 'blog/posts/featured',
                'transformation' => [
                    'width' => 1200,
                    'height' => 630,
                    'crop' => 'fill',
                    'quality' => 'auto:best'
                ]
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to upload featured image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete featured image from Cloudinary
     */
    public function deleteFeaturedImage(): bool
    {
        if (!$this->featured_image_public_id) {
            return false;
        }

        try {
            $cloudinaryService = app(CloudImageService::class);
            $deleted = $cloudinaryService->deleteImage($this->featured_image_public_id);
            
            if ($deleted) {
                $this->update([
                    'featured_image' => null,
                    'featured_image_public_id' => null
                ]);
            }
            
            return $deleted;
        } catch (\Exception $e) {
            logger()->error('Failed to delete featured image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => true,
                'unique' => true,
                'separator' => '-',
                'includeTrashed' => true
            ]
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}