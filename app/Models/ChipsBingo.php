<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ChipsBingo extends Model
{
    protected $table = "chips_bingo";
    protected $fillable = ['id', 'nombre', 'imagen'];
}