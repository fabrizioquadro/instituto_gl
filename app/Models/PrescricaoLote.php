<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoLote extends Model
{
    use HasFactory;

    protected $table = 'prescricao_lotes';

    protected $fillable = [
        'id_versao1',
        'prescricao_semana_medicamento_id',
        'quantidade',
        'lote',
        'codigo_barras',
        'estoque_aberto_id',
    ];

    public function medicamento()
    {
        return $this->belongsTo(PrescricaoSemanaMedicamento::class, 'prescricao_semana_medicamento_id');
    }

    public function estoqueAberto()
    {
        return $this->belongsTo(EstoqueAberto::class, 'estoque_aberto_id');
    }
}
