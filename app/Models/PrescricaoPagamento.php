<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoPagamento extends Model
{
    use HasFactory;

    protected $table = 'prescricao_pagamentos';

    protected $fillable = [
        'id_versao1',
        'prescricao_id',
        'dt_pagamento',
        'vl_total',
        'obs',
        'snapshot_parcelas',
        'user_id',
    ];

    protected $casts = [
        'snapshot_parcelas' => 'array',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_id');
    }

    public function formas()
    {
        return $this->hasMany(PrescricaoPagamentoForma::class, 'pagamento_id');
    }

    public function parcelas()
    {
        return $this->hasMany(PagamentoParcela::class, 'pagamento_id');
    }

    public function anexos()
    {
        return $this->hasMany(Anexo::class, 'pagamento_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
