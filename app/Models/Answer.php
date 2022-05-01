<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $primaryKey = 'answer_id';
    protected $table = 'tblanswers';
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
