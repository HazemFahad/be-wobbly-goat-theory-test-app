<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;
    protected $primaryKey = 'test_id';
    protected $table = 'tbltests';
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    
}
