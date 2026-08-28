<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anexo extends Model
{
    use HasFactory;

    protected $table = 'anexos';

    protected $fillable = [
        'id_versao1',
        'tipo',
        'prescricao_id',
        'pagamento_id',
        'user_id',
        'nm_anexo',
        'arquivo',
        'mime',
        'extensao',
        'visualizado_em',
        'visualizado_por',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_id');
    }

    public function pagamento()
    {
        return $this->belongsTo(PrescricaoPagamento::class, 'pagamento_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
