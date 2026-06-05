<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Baixa extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'user_id',
        'motivo',
        'data',
        'valor',
    ];

    public function medicamentos(){
        return $this->hasMany(Estoque::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
