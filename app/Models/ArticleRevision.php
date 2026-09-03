<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'user_id', 'title', 'body_html', 'excerpt', 'seo_title', 'meta_description', 'change_note', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
