<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstoqueAberto extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicamento_id',
        'procedimento_id',
        'user_id',
        'clinica_id',
        'identificador',
        'dt_cadastro',
        'qt_inical',
        'qt_utilizado',
        'qt_restante',
        'lote',
        'codigo_barras',
        'situacao',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function medicamento(){
        return $this->belongsTo(Medicamento::class);
    }
}
