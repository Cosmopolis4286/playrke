<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandLog extends Model
{
    use HasFactory;

    protected $table = 'command_logs';

    protected $fillable = ['command', 'output', 'status', 'executed_at'];

    protected $casts = ['executed_at' => 'datetime'];
}
