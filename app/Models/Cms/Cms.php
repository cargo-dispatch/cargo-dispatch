<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;



class Cms extends Model  implements AuditableContract
{
    use Auditable,SoftDeletes;
   protected $table = 'cms';
    protected $fillable = [
        'type',
        'title',
        'slug',
        'meta_tags',
        'meta_keywords',
        'image',
        'content',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Automatically generate slug from title
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // Accessor for image URL
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // Scope for active records
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for CMS type
    public function scopeCmsType($query)
    {
        return $query->where('type', 'CMS');
    }

    // Scope for Services type
    public function scopeServicesType($query)
    {
        return $query->where('type', 'Services');
    }

    // Find by slug
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}
