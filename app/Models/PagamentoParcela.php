<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagamentoParcela extends Model
{
    use HasFactory;

    protected $table = 'pagamento_parcelas';

    protected $fillable = [
        'id_versao1',
        'pagamento_id',
        'financeiro_parcela_id',
        'valor',
    ];

    public function pagamento()
    {
        return $this->belongsTo(PrescricaoPagamento::class, 'pagamento_id');
    }

    public function financeiroParcela()
    {
        return $this->belongsTo(FinanceiroParcela::class, 'financeiro_parcela_id');
    }
}
