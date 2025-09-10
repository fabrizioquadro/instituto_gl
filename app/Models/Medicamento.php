<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'fabricante',
        'unidade',
        'vasilhame',
        'ultimo_valor_pg',
        'vl_venda',
        'estoque_minimo',
        'situacao',
    ];
}
