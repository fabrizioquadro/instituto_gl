@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Enfermagem (Prescrições) - Resultados</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Chegada</th>
                        <th>Atendimento</th>
                        <th>Tipo</th>
                        <th>Finalização</th>
                        <th>Aplicação</th>
                        <th>Paciente</th>
                        <th>Enfermeira</th>
                        <th>Clínica</th>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Valor Semana</th>
                        <th>Lote</th>
                        <th>C. Barras</th>
                        <th>Validade</th>
                        <th>Obs</th>
                        <th>Prescrição</th>
                        <th>Semana</th>
                        <th>Situação Fin.</th>
                        <th>Coord.</th>
                        <th>Qual.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itens as $item)
                        @php
                            $semana = $item->semana;
                            $prescricao = $semana->prescricao ?? null;
                            $parcela = $semana->parcela ?? null;
                            $enfermeira = $item->userAplicacao ?: $semana->userAplicacao;
                            $chegada = $item->dt_hr_chegada ?: $semana->dt_hr_chegada;
                            $atendimento = $item->dt_hr_atendimento ?: $semana->dt_hr_atendimento;
                        @endphp
                        <tr>
                            <td>{{ $chegada ? dataDbForm(explode(' ', $chegada)[0]) . ' ' . (explode(' ', $chegada)[1] ?? '') : '' }}</td>
                            <td>{{ $atendimento ? dataDbForm(explode(' ', $atendimento)[0]) . ' ' . (explode(' ', $atendimento)[1] ?? '') : '' }}</td>
                            <td>{{ $prescricao->tipo_atendimento ?? '' }}</td>
                            <td>
                                @if($semana->dt_hr_finalizacao)
                                    {{ dataDbForm(explode(' ', $semana->dt_hr_finalizacao)[0]) }}
                                    {{ explode(' ', $semana->dt_hr_finalizacao)[1] ?? '' }}
                                @endif
                            </td>
                            <td>
                                @if($item->aplicado_em)
                                    {{ dataDbForm(explode(' ', $item->aplicado_em)[0]) }}
                                    {{ explode(' ', $item->aplicado_em)[1] ?? '' }}
                                @endif
                            </td>
                            <td>{{ $prescricao->paciente->nm_paciente ?? 'N/A' }}</td>
                            <td>{{ $enfermeira->nome ?? '' }}</td>
                            <td>
                                {{ $item->clinica_aplicacao_nome ?: ($prescricao->clinica->nome ?? 'N/A') }}
                            </td>
                            <td>{{ $item->medicamento->nome ?? 'N/A' }}</td>
                            <td>{{ valorDbForm($item->quantidade) }}</td>
                            <td>R$ {{ valorDbForm($parcela->valor_parcela ?? 0) }}</td>
                            <td>{!! $item->lotes_txt !!}</td>
                            <td>{!! $item->codigos_txt !!}</td>
                            <td>{!! $item->vencimentos_txt !!}</td>
                            <td>{{ $item->obs }}</td>
                            <td>
                                @if($prescricao)
                                    <a href="{{ route('sistema.prescricoes.acessar_semana', $semana->id) }}" target="_blank">
                                        {{ $prescricao->codigo_versao1 ?: $prescricao->id }}
                                    </a>
                                @endif
                            </td>
                            <td>{{ $semana->nr_semana ?? '' }}</td>
                            <td>{{ $prescricao->situacao_financeira ?? '' }}</td>
                            <td>{{ $semana->flag_coordenacao ? 'Sim' : 'Não' }}</td>
                            <td>{{ $semana->flag_qualidade ? 'Sim' : 'Não' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<form target="_blank" id='formulario' action="{{ route('adm.relatorios2.exportar_enfermagem') }}" method="post">
    @csrf
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>

<script>
document.getElementById('exportar').addEventListener('click', () => {
    document.getElementById('formulario').submit();
});
</script>
@endsection
