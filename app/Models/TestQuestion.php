<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    use HasFactory;
    protected $primaryKey = 'test_questions_id';
    protected $table = 'tbltest_questions';
    protected $hidden = [
        'created_at',
        'updated_at',
    ];


}
