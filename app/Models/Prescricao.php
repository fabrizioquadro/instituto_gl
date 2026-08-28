<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescricao extends Model
{
    use HasFactory;

    protected $table = 'prescricaos';

    protected $fillable = [
        'codigo_versao1',
        'paciente_id',
        'clinica_id',
        'user_id_cadastro',
        'medico',
        'tipo_atendimento',
        'agendamento',
        'obs',
        'data_prescricao',
        'qt_semanas',
        'qt_semanas_aplicacao',
        'qt_parcelas',
        'semana_atual',
        'valor_tratamento',
        'credito_em_aberto',
        'situacao',
        'situacao_financeira',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function userCadastro()
    {
        return $this->belongsTo(User::class, 'user_id_cadastro');
    }

    public function semanas()
    {
        return $this->hasMany(PrescricaoSemana::class, 'prescricao_id')->orderBy('nr_semana');
    }

    public function medicamentos()
    {
        return $this->hasManyThrough(PrescricaoSemanaMedicamento::class, PrescricaoSemana::class, 'prescricao_id', 'prescricao_semana_id');
    }

    public function parcelas()
    {
        return $this->hasMany(FinanceiroParcela::class, 'prescricao_id');
    }

    public function pagamentos()
    {
        return $this->hasMany(PrescricaoPagamento::class, 'prescricao_id');
    }

    public function anexos()
    {
        return $this->hasMany(Anexo::class, 'prescricao_id');
    }

    public function logs()
    {
        return $this->hasMany(PrescricaoLog::class, 'prescricao_id');
    }

    public function observacoes()
    {
        return $this->hasManyThrough(PrescricaoObservacao::class, PrescricaoSemana::class, 'prescricao_id', 'prescricao_semana_id');
    }

    /**
     * Soma total paga (via pagamentos) da prescrição.
     */
    public function getTotalPagoAttribute()
    {
        return $this->pagamentos()->sum('vl_total');
    }

    /**
     * Consulta server-side para a DataTable da listagem de prescrições.
     */
    public static function index_pesq($requestData)
    {
        $query = SELF::with('paciente', 'userCadastro', 'semanas');

        $totalFiltered = $query->count();

        if (!empty($requestData['search']['value'])) {
            $busca = $requestData['search']['value'];
            $pacientes = Paciente::where('nm_paciente', 'LIKE', "%$busca%")->pluck('id')->toArray();

            $query->where(function ($q) use ($busca, $pacientes) {
                $q->where('medico', 'LIKE', "%$busca%")
                    ->orWhere('codigo_versao1', 'LIKE', "%$busca%")
                    ->orWhereIn('paciente_id', $pacientes);
            });

            $totalFiltered = $query->count();
        }

        $prescricoes = $query->orderByDesc('data_prescricao')
            ->offset($requestData['start'])
            ->limit($requestData['length'])
            ->get();

        return [
            'prescricoes' => $prescricoes,
            'totalFiltered' => $totalFiltered,
        ];
    }

    /**
     * Última semana que foi aplicada (maior nr_semana com situação
     * Aplicada ou Aplicação Parcial). Retorna o model PrescricaoSemana ou null.
     */
    public function get_semana_aplicada()
    {
        return $this->semanas->filter(function ($s) {
            return in_array($s->situacao, ['Aplicada', 'Aplicação Parcial']);
        })->last();
    }

    /**
     * Próxima semana que precisa ser aplicada (menor nr_semana com tem_aplicacao
     * e situação ainda pendente). Retorna o model PrescricaoSemana ou null.
     */
    public function get_semana_aplicar()
    {
        return $this->semanas->first(function ($s) {
            return $s->tem_aplicacao && in_array($s->situacao, ['Agendada', 'Em Atendimento', 'Aplicação Parcial']);
        });
    }

    /**
     * Data da última edição (maior created_at dos logs de atualização).
     */
    public function get_ultima_edicao()
    {
        return PrescricaoLog::where('prescricao_id', $this->id)
            ->where('acao', 'Atualização')
            ->max('created_at');
    }
}
