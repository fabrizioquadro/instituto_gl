<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AplicacaoLote extends Model
{
    use HasFactory;

    protected $fillable = [
        'aplicacao_id',
        'quantidade',
        'lote',
        'codigo_barras',
        'estoque_aberto_id',
    ];
}
