<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoSemanaMedicamento extends Model
{
    use HasFactory;

    protected $table = 'prescricao_semana_medicamentos';

    protected $fillable = [
        'id_versao1',
        'prescricao_semana_id',
        'medicamento_id',
        'combo_id',
        'clinica_id_aplicacao',
        'is_soro',
        'gera_aplicacao',
        'quantidade',
        'situacao',
        'data_prevista',
        'dt_hr_chegada',
        'dt_hr_atendimento',
        'aplicado_em',
        'user_id_aplicacao',
        'obs',
    ];

    protected $casts = [
        'is_soro' => 'boolean',
        'gera_aplicacao' => 'boolean',
    ];

    public function semana()
    {
        return $this->belongsTo(PrescricaoSemana::class, 'prescricao_semana_id');
    }

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class, 'medicamento_id');
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id');
    }

    public function lotes()
    {
        return $this->hasMany(PrescricaoLote::class, 'prescricao_semana_medicamento_id');
    }

    public function userAplicacao()
    {
        return $this->belongsTo(User::class, 'user_id_aplicacao');
    }
}
