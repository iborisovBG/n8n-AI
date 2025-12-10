<?php

namespace App\Models;

use Database\Factories\AdScriptTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdScriptTask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_script',
        'outcome_description',
        'new_script',
        'analysis',
        'status',
        'error_details',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AdScriptTaskFactory::new();
    }
}

