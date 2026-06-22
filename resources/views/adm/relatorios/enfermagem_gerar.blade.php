@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório Enfermagem</h4>
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
                        <th>Clinica</th>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Unitário</th>
                        <th>Valor</th>
                        <th>Lote</th>
                        <th>C. Barras</th>
                        <th>Validade</th>
                        <th>Obs</th>
                        <th>Procedimento</th>
                        <th>Pagamento</th>
                        <th>Coord.</th>
                        <th>Qual.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($procedimentos as $procedimento)
                        @php
                        $chegada = "";
                        $atendimento = "";
                        $finalizacao = "";

                        if($procedimento->dt_hr_chegada){
                            $var = explode(' ',$procedimento->dt_hr_chegada);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                        } elseif ($procedimento->inicio_cadastro) {
                            $var = explode(' ',$procedimento->inicio_cadastro);
                            $chegada = dataDbForm($var[0])." ".($var[1] ?? '00:00:00');
                        }

                        if($procedimento->dt_hr_atendimento){
                            $var = explode(' ',$procedimento->dt_hr_atendimento);
                            $atendimento = dataDbForm($var[0])." ".$var[1];
                        }

                        if($procedimento->dt_hr_finalizacao){
                            $var = explode(' ',$procedimento->dt_hr_finalizacao);
                            $finalizacao = dataDbForm($var[0])." ".$var[1];
                        }
                        @endphp
                        @foreach($procedimento->aplicacaos as $aplicacao)
                            @if($aplicacao->situacao == 'Aplicada')
                                @php
                                $var = explode(' ', $aplicacao->updated_at);
                                $data_aplicada = $var[0];
                                $data = dataDbForm($data_aplicada);
                                $hora = $var[1];

                                // Filtrar por intervalo de datas se informado
                                $exibir = true;
                                if(isset($dados['dt_inc']) && $dados['dt_inc'] && $data_aplicada < $dados['dt_inc']){
                                    $exibir = false;
                                }
                                if(isset($dados['dt_fn']) && $dados['dt_fn'] && $data_aplicada > $dados['dt_fn']){
                                    $exibir = false;
                                }

                                // Horários de Chegada/Atendimento com fallback para a data/hora da própria aplicação
                                $app_chegada = $aplicacao->dt_hr_chegada ?? $aplicacao->updated_at;
                                $chegada_val = "";
                                if($app_chegada){
                                    $var_c = explode(' ', $app_chegada);
                                    $chegada_val = dataDbForm($var_c[0])." ".($var_c[1] ?? '00:00:00');
                                }

                                $app_atendimento = $aplicacao->dt_hr_atendimento ?? $aplicacao->updated_at;
                                $atendimento_val = "";
                                if($app_atendimento){
                                    $var_a = explode(' ', $app_atendimento);
                                    $atendimento_val = dataDbForm($var_a[0])." ".($var_a[1] ?? '00:00:00');
                                }
                                @endphp
                                @if($exibir)
                                    <tr>
                                        <td>{{ $chegada_val }}</td>
                                        <td>{{ $atendimento_val }}</td>
                                        <td>{{ $procedimento->tipo_atendimento }}</td>
                                        <td>{{ $finalizacao }}</td>
                                        <td>{{ $data }} {{ $hora }}</td>
                                        <td>{{ $procedimento->paciente->nm_paciente }}</td>
                                        <td>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '' }}</td>
                                        <td>{{ $procedimento->clinica_aplicacao->nome }}</td>
                                        <td>{{ $aplicacao->medicamento->nome }}</td>
                                        <td>{{ $aplicacao->quantidade }}</td>
                                        <td>R$ {{ valorDbForm($aplicacao->valor) }}</td>
                                        <td>R$ {{ valorDbForm($aplicacao->total) }}</td>
                                        <td>{{ $aplicacao->lotes() }}</td>
                                        <td>{{ $aplicacao->codigos() }}</td>
                                        <td>{{ $aplicacao->vencimentos() }}</td>
                                        <td>{{ $aplicacao->obs }}</td>
                                        <td>{{ $procedimento->codigo.'/'.$procedimento->nr_procedimento }}</td>
                                        <td>{{ $procedimento->st_pagamento }}</td>
                                        <td>{{ $procedimento->flag_coordenacao == 1 ? 'Sim' : 'Não' }}</td>
                                        <td>{{ $procedimento->flag_qualidade == 1 ? 'Sim' : 'Não' }}</td>
                                    </tr>
                                @endif
                            @endif
                        @endforeach
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
</div>
<form target="_blank" id='formulario' action="{{ route('adm.relatorios.exportar_enfermagem') }}" method="post">
    @csrf
    <input type="hidden" name="data" id="data">
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>
<script>
document.getElementById('exportar').addEventListener('click', ()=>{
    dados = document.getElementById('div_dados').innerHTML;
    document.getElementById('data').value = dados;
    document.getElementById('formulario').submit();
})

</script>
@endsection
