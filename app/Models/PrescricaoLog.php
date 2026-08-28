<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoLog extends Model
{
    use HasFactory;

    protected $table = 'prescricao_logs';

    protected $fillable = [
        'id_versao1',
        'prescricao_id',
        'entidade',
        'entidade_id',
        'user_id',
        'acao',
        'descricao',
        'dados_antigos',
        'dados_novos',
    ];

    protected $casts = [
        'dados_antigos' => 'array',
        'dados_novos' => 'array',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
