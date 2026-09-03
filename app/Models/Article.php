<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    protected $fillable = ['category_id', 'title', 'slug', 'excerpt', 'body_html', 'body_text', 'status', 'published_at', 'sort_order',
        'seo_title', 'meta_description', 'canonical_url', 'noindex', 'og_image_id', 'created_by', 'updated_by'];
    protected $casts = ['noindex' => 'bool', 'published_at' => 'datetime'];

    public function category() { return $this->belongsTo(Category::class); }
    public function tags() { return $this->belongsToMany(Tag::class); }
    public function revisions() { return $this->hasMany(ArticleRevision::class)->latest('id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function editor() { return $this->belongsTo(User::class, 'updated_by'); }
    public function ogImage() { return $this->belongsTo(Media::class, 'og_image_id'); }
    public function scopePublished(Builder $q): Builder { return $q->where('status', 'published'); }
    public function isPublished(): bool { return $this->status === 'published'; }
    public function path(): string { return $this->category->path().'/'.$this->slug; }
    public function url(): string { return url('/'.$this->path()); }
}
