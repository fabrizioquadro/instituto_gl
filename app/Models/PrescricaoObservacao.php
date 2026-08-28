<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoObservacao extends Model
{
    use HasFactory;

    protected $table = 'prescricao_observacaos';

    protected $fillable = [
        'id_versao1',
        'prescricao_semana_id',
        'user_id',
        'observacao',
    ];

    public function semana()
    {
        return $this->belongsTo(PrescricaoSemana::class, 'prescricao_semana_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
