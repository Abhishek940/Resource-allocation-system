<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'emp_id',
        'roleName',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
