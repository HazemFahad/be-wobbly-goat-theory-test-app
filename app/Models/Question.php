<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $primaryKey = 'question_id';
    protected $table = 'tblquestions';
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    
    public function index()
    {
        $test['test'] = [
            '/api' => 'index',
        ];
        return $test;
    }

    
}
