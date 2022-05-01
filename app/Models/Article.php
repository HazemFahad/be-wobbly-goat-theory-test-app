<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'body'];

    protected $primaryKey = 'article_id';
    protected $table = 'tblarticles';
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    
}
