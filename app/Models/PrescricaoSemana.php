<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoSemana extends Model
{
    use HasFactory;

    protected $table = 'prescricao_semanas';

    protected $fillable = [
        'id_versao1',
        'prescricao_id',
        'nr_semana',
        'data_prevista',
        'data_aplicada',
        'tem_aplicacao',
        'situacao',
        'dt_hr_chegada',
        'dt_hr_atendimento',
        'dt_hr_finalizacao',
        'user_id_aplicacao',
        'obs',
        'flag_coordenacao',
        'flag_qualidade',
        'user_nome_coordenacao',
        'user_nome_qualidade',
    ];

    protected $casts = [
        'tem_aplicacao' => 'boolean',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_id');
    }

    public function medicamentos()
    {
        return $this->hasMany(PrescricaoSemanaMedicamento::class, 'prescricao_semana_id');
    }

    public function parcela()
    {
        return $this->hasOne(FinanceiroParcela::class, 'prescricao_semana_id');
    }

    public function observacoes()
    {
        return $this->hasMany(PrescricaoObservacao::class, 'prescricao_semana_id');
    }

    public function userAplicacao()
    {
        return $this->belongsTo(User::class, 'user_id_aplicacao');
    }
}
