<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'fornecedor_id',
        'nota',
        'data',
        'valor',
        'arquivo',
    ];

    public function fornecedor(){
        return $this->belongsTo(Fornecedor::class);
    }

    public function medicamentos(){
        return $this->hasMany(Estoque::class);
    }
}
