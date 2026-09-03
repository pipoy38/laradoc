<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'icon', 'sort_order'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->ordered(); }
    public function articles() { return $this->hasMany(Article::class)->orderBy('sort_order')->orderBy('title'); }
    public function scopeOrdered(Builder $q): Builder { return $q->orderBy('sort_order')->orderBy('name'); }

    /** Root-first chain of ancestors, excluding self. */
    public function ancestors(): Collection
    {
        $chain = collect(); $node = $this->parent;
        while ($node) { $chain->prepend($node); $node = $node->parent; }
        return $chain;
    }

    public function path(): string
    {
        return $this->ancestors()->push($this)->pluck('slug')->implode('/');
    }

    public function url(): string { return url('/'.$this->path()); }

    /** Self plus every descendant id, depth-first. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) { $ids = array_merge($ids, $child->descendantIds()); }
        return $ids;
    }

    /** True when $candidate is this category or one of its descendants (used to forbid cyclic parents). */
    public function isOrContains(?int $candidateId): bool
    {
        return $candidateId !== null && in_array($candidateId, $this->descendantIds(), true);
    }
}
