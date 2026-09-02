<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeegowFila extends Model
{
    use HasFactory;

    protected $table = 'feegow_filas';

    protected $fillable = [
        'prescricao_id',
        'prescricao_semana_id',
        'evento',
        'procedimento_id',
        'payload',
        'situacao',
        'tentativas',
        'proxima_tentativa',
        'ultima_tentativa',
        'erro',
        'enviado_em',
    ];

    protected $casts = [
        'payload' => 'array',
        'tentativas' => 'integer',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_id');
    }

    public function semana()
    {
        return $this->belongsTo(PrescricaoSemana::class, 'prescricao_semana_id');
    }
}
