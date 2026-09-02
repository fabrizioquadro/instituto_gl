@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)
@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title">Fila Atendimento</h4>
        <hr>
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Aguardando</h5>
                <div class="table-responsive">
                    <table class="tabela-index table" id="table-index">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Procedimentos</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos_aguardando as $procedimento)
                            @php
                            $qt_aplicacao = 0;
                            foreach($procedimento->aplicacaos as $app){
                                if($app->medicamento && $app->medicamento->aplicacao == 'Sim'){
                                    $qt_aplicacao++;
                                }
                            }
                            if($qt_aplicacao == 0 && $procedimento->st_biopedancia != 'Sim' && $procedimento->st_coleta != 'Sim'){
                                continue;
                            }

                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($qt_aplicacao > 0){
                                $ds_procedimentos .= ", Aplicação";
                            }
                            if($procedimento->st_biopedancia == 'Sim'){
                                $ds_procedimentos .= ", Biopedância";
                            }
                            if($procedimento->st_coleta == 'Sim'){
                                $ds_procedimentos .= ", Coleta";
                            }
                            $ds_procedimentos = substr($ds_procedimentos, 2);

                            if($procedimento->situacao == "Atendimento"){
                                $situacao = '<span class="badge rounded-pill bg-label-success">Atendimento</span>';
                            }
                            elseif($procedimento->situacao == "Fila de Aplicação"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Aguardando</span>';
                            }

                            @endphp
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu" data-popper-placement="bottom-end">
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Abrir Atendimento</a>
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_visualizar_procedimento', $procedimento->id) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                        </div>
                                    </div>
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>
                                    {{ $procedimento->paciente->nm_paciente }}
                                    <button onclick="abrir_obs_leitura({{ $procedimento->paciente->id }})" class="btn btn-xs btn-outline-info p-1 py-0 ms-1" type="button" title="Ver Observações"><i class="mdi mdi-comment-text-outline"></i></button>
                                </td>
                                <td>{{ $ds_procedimentos }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{!! $situacao !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Atendimentos</h5>
                <div class="table-responsive">
                    <table class="tabela-index table" id="table-index">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Procedimentos</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos_atendimento as $procedimento)
                            @php
                            $qt_aplicacao = 0;
                            foreach($procedimento->aplicacaos as $app){
                                if($app->medicamento && $app->medicamento->aplicacao == 'Sim'){
                                    $qt_aplicacao++;
                                }
                            }
                            if($qt_aplicacao == 0 && $procedimento->st_biopedancia != 'Sim' && $procedimento->st_coleta != 'Sim'){
                                continue;
                            }

                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($qt_aplicacao > 0){
                                $ds_procedimentos .= ", Aplicação";
                            }
                            if($procedimento->st_biopedancia == 'Sim'){
                                $ds_procedimentos .= ", Biopedância";
                            }
                            if($procedimento->st_coleta == 'Sim'){
                                $ds_procedimentos .= ", Coleta";
                            }
                            $ds_procedimentos = substr($ds_procedimentos, 2);

                            if($procedimento->situacao == "Atendimento"){
                                $situacao = '<span class="badge rounded-pill bg-label-success">Atendimento</span>';
                            }
                            elseif($procedimento->situacao == "Fila de Aplicação"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Aguardando</span>';
                            }

                            @endphp
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu" data-popper-placement="bottom-end">
                                            @if($procedimento->user_id_aplicacao == $user->id)
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Abrir Atendimento</a>
                                            @endif
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_visualizar_procedimento', $procedimento->id) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                        </div>
                                    </div>
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>
                                    {{ $procedimento->paciente->nm_paciente }}
                                    <button onclick="abrir_obs_leitura({{ $procedimento->paciente->id }})" class="btn btn-xs btn-outline-info p-1 py-0 ms-1" type="button" title="Ver Observações"><i class="mdi mdi-comment-text-outline"></i></button>
                                </td>
                                <td>{{ $ds_procedimentos }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{!! $situacao !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Aplicadas</h5>
                <div class="table-responsive">
                    <table class="tabela-index table" id="table-index">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Procedimentos</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos_aplicadas as $procedimento)
                            @php
                            $qt_aplicacao = 0;
                            foreach($procedimento->aplicacaos as $app){
                                if($app->medicamento && $app->medicamento->aplicacao == 'Sim'){
                                    $qt_aplicacao++;
                                }
                            }
                            if($qt_aplicacao == 0 && $procedimento->st_biopedancia != 'Sim' && $procedimento->st_coleta != 'Sim'){
                                continue;
                            }

                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($qt_aplicacao > 0){
                                $ds_procedimentos .= ", Aplicação";
                            }
                            if($procedimento->st_biopedancia == 'Sim'){
                                $ds_procedimentos .= ", Biopedância";
                            }
                            if($procedimento->st_coleta == 'Sim'){
                                $ds_procedimentos .= ", Coleta";
                            }
                            $ds_procedimentos = substr($ds_procedimentos, 2);

                            if($procedimento->situacao == "Atendimento"){
                                $situacao = '<span class="badge rounded-pill bg-label-success">Atendimento</span>';
                            }
                            elseif($procedimento->situacao == "Fila de Aplicação"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Aguardando</span>';
                            }
                            elseif($procedimento->situacao == "Aplicado"){
                                $situacao = '<span class="badge rounded-pill bg-label-primary">Aplicado</span>';
                            }

                            @endphp
                            <tr>
                                <td>
                                    @if($procedimento->user_id_aplicacao == $user->id)
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu" data-popper-placement="bottom-end">
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Abrir Atendimento</a>
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_visualizar_procedimento', $procedimento->id) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>
                                    {{ $procedimento->paciente->nm_paciente }}
                                    <button onclick="abrir_obs_leitura({{ $procedimento->paciente->id }})" class="btn btn-xs btn-outline-info p-1 py-0 ms-1" type="button" title="Ver Observações"><i class="mdi mdi-comment-text-outline"></i></button>
                                </td>
                                <td>{{ $ds_procedimentos }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{!! $situacao !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-border-shadow-info mb-4">
            <div class="card-body">
                <h5 class="card-title">Resumo de Atendimentos do Dia</h5>
                @php
                    $resumo_enfermeiras = [];
                    $total_pacientes = 0;
                    $total_aplicacao = 0;
                    $total_bio = 0;
                    $total_coleta = 0;
                    foreach($procedimentos_aplicadas as $proc){
                        if(str_contains($proc->tipo_atendimento, 'Consulta') || str_contains($proc->tipo_atendimento, 'Retorno')) {
                            continue;
                        }

                        $qt_aplicacao = 0;
                        foreach($proc->aplicacaos as $app){
                            if($app->medicamento && $app->medicamento->aplicacao == 'Sim'){
                                $qt_aplicacao++;
                            }
                        }
                        if($qt_aplicacao == 0 && $proc->st_biopedancia != 'Sim' && $proc->st_coleta != 'Sim'){
                            continue;
                        }

                        $nome = $proc->aplicadora ? $proc->aplicadora->nome : 'Não Identificada';
                        if(!isset($resumo_enfermeiras[$nome])){
                            $resumo_enfermeiras[$nome] = [
                                'pacientes' => 0,
                                'aplicacao' => 0,
                                'bio' => 0,
                                'coleta' => 0
                            ];
                        }
                        $resumo_enfermeiras[$nome]['pacientes']++;
                        $total_pacientes++;

                        if($qt_aplicacao > 0){
                            $resumo_enfermeiras[$nome]['aplicacao']++;
                            $total_aplicacao++;
                        }
                        if($proc->st_biopedancia == 'Sim'){
                            $resumo_enfermeiras[$nome]['bio']++;
                            $total_bio++;
                        }
                        if($proc->st_coleta == 'Sim'){
                            $resumo_enfermeiras[$nome]['coleta']++;
                            $total_coleta++;
                        }
                    }
                    ksort($resumo_enfermeiras);
                    $total_atendidos = $total_pacientes;
                @endphp
                <div class="row">
                    <div class="col-md-10">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Enfermeira</th>
                                        <th class="text-center">Qtd. Pacientes</th>
                                        <th class="text-center">Qtd. Aplicação</th>
                                        <th class="text-center">Qtd. Bio</th>
                                        <th class="text-center">Qtd. Coleta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($resumo_enfermeiras as $enfermeira => $dados)
                                        <tr>
                                            <td>{{ $enfermeira }}</td>
                                            <td class="text-center"><b>{{ $dados['pacientes'] }}</b></td>
                                            <td class="text-center"><b>{{ $dados['aplicacao'] }}</b></td>
                                            <td class="text-center"><b>{{ $dados['bio'] }}</b></td>
                                            <td class="text-center"><b>{{ $dados['coleta'] }}</b></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>TOTAL GERAL</th>
                                        <th class="text-center">{{ $total_pacientes }}</th>
                                        <th class="text-center">{{ $total_aplicacao }}</th>
                                        <th class="text-center">{{ $total_bio }}</th>
                                        <th class="text-center">{{ $total_coleta }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="mb-0">Total de pacientes atendidos hoje: <strong>{{ $total_atendidos }}</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
window.addEventListener('load',()=>{
  $('#table-index').DataTable({
    order: [[1, 'asc']],
    "language": {
			"sEmptyTable": "Nenhum registro encontrado",
      "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
      "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
      "sInfoFiltered": "(Filtrados de _MAX_ registros)",
      "sInfoPostFix": "",
      "sInfoThousands": ".",
      "sLengthMenu": "_MENU_ resultados por página",
      "sLoadingRecords": "Carregando...",
      "sProcessing": "Processando...",
      "sZeroRecords": "Nenhum registro encontrado",
      "sSearch": "Pesquisar",
      "oPaginate": {
        "sNext": "Próximo",
        "sPrevious": "Anterior",
        "sFirst": "Primeiro",
        "sLast": "Último"
      },
      "oAria": {
        "sSortAscending": ": Ordenar colunas de forma ascendente",
        "sSortDescending": ": Ordenar colunas de forma descendente"
      }
    }
  });
});

var modalObservacoesLeitura;

function abrir_obs_leitura(paciente_id){
    $.getJSON(
        "{{ route('sistema.pacientes.get_paciente_ajax') }}",
        {
            paciente_id : paciente_id
        },
        function(json){
            document.getElementById('modal_observacoes_leitura_titulo').innerText = 'Observações: ' + json.nm_paciente;
            let obs = json.obs ? json.obs : 'Nenhuma observação registrada para este paciente.';
            document.getElementById('modal_observacoes_leitura_texto').value = obs;
            
            modalObservacoesLeitura = new bootstrap.Modal(document.getElementById('modal_observacoes_leitura'));
            modalObservacoesLeitura.show();
        }
    );
}
</script>

<!-- Modal Observações (Leitura) -->
<div class="modal fade" id="modal_observacoes_leitura" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_observacoes_leitura_titulo">Observações do Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col mb-3">
                        <textarea id="modal_observacoes_leitura_texto" class="form-control" rows="8" readonly style="resize: none; background-color: #f8f9fa;"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection
