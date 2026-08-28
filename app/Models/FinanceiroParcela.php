<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceiroParcela extends Model
{
    use HasFactory;

    protected $table = 'financeiro_parcelas';

    protected $fillable = [
        'id_versao1',
        'prescricao_id',
        'prescricao_semana_id',
        'nr_parcela',
        'valor_parcela',
        'valor_pago',
        'situacao',
        'dt_vencimento',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_id');
    }

    public function semana()
    {
        return $this->belongsTo(PrescricaoSemana::class, 'prescricao_semana_id');
    }

    public function pagamentoParcelas()
    {
        return $this->hasMany(PagamentoParcela::class, 'financeiro_parcela_id');
    }

    public function getTotalPagoAttribute()
    {
        return $this->pagamentoParcelas()->sum('valor');
    }
}
