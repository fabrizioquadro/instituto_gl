@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)
@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title">Enfermagem</h4>
        @if($mensagem = Session::get('mensagem'))
            <div class="alert alert-success alert-dismissible mt-3" role="alert">
                {{ $mensagem }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($mensagem = Session::get('mensagem_erro'))
            <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                {{ $mensagem }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
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
                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($procedimento->aplicacaos->count() > 0){
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
                                        </div>
                                    </div>
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>{{ $procedimento->paciente->nm_paciente }}</td>
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
                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($procedimento->aplicacaos->count() > 0){
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
                                    @if($procedimento->user_id_aplicacao == $user->id)
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu" data-popper-placement="bottom-end">
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Abrir Atendimento</a>
                                            </div>
                                        </div>
                                    @else
                                        {{ $procedimento->id }}
                                    @endif
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>{{ $procedimento->paciente->nm_paciente }}</td>
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
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Procedimentos</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos_aplicadas as $procedimento)
                            @php
                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($procedimento->aplicacaos->count() > 0){
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
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>{{ $procedimento->paciente->nm_paciente }}</td>
                                <td>{{ $ds_procedimentos }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{!! $situacao !!}</td>
                            </tr>
                        @endforeach
                    </table>
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
})

</script>
@endsection
