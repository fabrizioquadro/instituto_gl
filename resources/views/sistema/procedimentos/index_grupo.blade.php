@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Procedimentos</h4>
            <a href="{{ route('sistema.procedimentos.adicionar_grupo',$codigo) }}" class="btn btn-primary">Adicionar</a>
        </div>
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
        <div class="table-responsive">
            <table class="tabela-index table" id="table-index">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Dt Cad</th>
                        <th>Paciente</th>
                        <th>Procedimento</th>
                        <th>Semana</th>
                        <th>Médico</th>
                        <th>Dt Aplicação</th>
                        <th>Valor</th>
                        <th>Situação Pg</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                @foreach($procedimentos as $procedimento)
                    @php
                    if($procedimento->situacao == "Agendado"){
                        $situacao = '<span class="badge rounded-pill bg-label-warning">Agendado</span>';
                    }
                    elseif($procedimento->situacao == "Fila de Aplicação"){
                        $situacao = '<span class="badge rounded-pill bg-label-primary">Fila de Aplicação</span>';
                    }
                    elseif($procedimento->situacao == "Atendimento"){
                        $situacao = '<span class="badge rounded-pill bg-label-danger">Fila de Aplicação</span>';
                    }
                    elseif($procedimento->situacao == "Aplicado"){
                        $situacao = '<span class="badge rounded-pill bg-label-success">Aplicado</span>';
                    }
                    elseif($procedimento->situacao == "Semana Sem Aplicação"){
                        $situacao = '<span class="badge rounded-pill bg-label-secondary">Sem Aplicação</span>';
                    }
                    elseif($procedimento->situacao == "Pendente"){
                        $situacao = '<span class="badge rounded-pill bg-label-warning">Pendente</span>';
                    }

                    if($procedimento->st_pagamento == 'Sim'){
                        $st_pagamento = "<span class='badge bg-success'>$procedimento->st_pagamento</span>";
                    }
                    else{
                        $st_pagamento = "<span class='badge bg-danger'>$procedimento->st_pagamento</span>";
                    }

                    @endphp
                    <tr>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu" data-popper-placement="bottom-end">
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.acessar', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Acessar</a>
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.editar', $procedimento->id) }}"><i class="mdi mdi-pencil me-1"></i> Editar</a>
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.excluir', $procedimento->id) }}"><i class="mdi mdi-trash-can-outline me-1"></i> Excluir</a>
                                </div>
                            </div>
                        </td>
                        <td> <span style='display: none'>{{ strtotime($procedimento->data_cad) }}</span> {{ dataDbForm($procedimento->data_cad) }}</td>
                        <td>{{ $procedimento->paciente->nm_paciente }}</td>
                        <td>{{ $procedimento->codigo }}</td>
                        <td>{{ $procedimento->nr_procedimento }}</td>
                        <td>{{ $procedimento->medico }}</td>
                        <td>{{ dataDbForm($procedimento->data_aplicacao) }}</td>
                        <td>{{ valorDbForm($procedimento->valor) }}</td>
                        <td>{!! $st_pagamento !!}</td>
                        <td>{!! $situacao !!}</td>
                    </tr>
                @endforeach
            </table>
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
