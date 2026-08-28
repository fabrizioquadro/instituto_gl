<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoPagamentoForma extends Model
{
    use HasFactory;

    protected $table = 'prescricao_pagamento_formas';

    protected $fillable = [
        'id_versao1',
        'pagamento_id',
        'forma_pagamento',
        'vl_pagamento',
        'parcelas',
        'id_transacao',
        'obs',
    ];

    public function pagamento()
    {
        return $this->belongsTo(PrescricaoPagamento::class, 'pagamento_id');
    }
}
