@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Procedimentos</h4>
            <a href="{{ route('sistema.procedimentos.adicionar') }}" class="btn btn-primary">Adicionar</a>
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
                        <th>Semanas</th>
                        <th>Médico</th>
                        <th>Dt 1ª Aplicação</th>
                        <th>Situação Pg</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                @foreach($procedimentos as $procedimento)
                    @php
                    $st_procedimento = $procedimento->get_st_procedimento();
                    if($st_procedimento == "Aberto"){
                        $situacao = '<span class="badge rounded-pill bg-label-warning">'.$st_procedimento.'</span>';
                    }
                    elseif($st_procedimento == "Finalizado"){
                        $situacao = '<span class="badge rounded-pill bg-label-success">'.$st_procedimento.'</span>';
                    }
                    elseif($st_procedimento == "Cancelado"){
                        $situacao = '<span class="badge rounded-pill bg-label-danger">'.$st_procedimento.'</span>';
                    }

                    $st_pagamento = $procedimento->get_st_pagamento();
                    if($st_pagamento == 'Aberto'){
                        $st_pagamento = "<span class='badge bg-danger'>$st_pagamento</span>";
                    }
                    elseif($st_pagamento == 'Total'){
                        $st_pagamento = "<span class='badge bg-success'>$st_pagamento</span>";
                    }
                    elseif($st_pagamento == 'Parcial'){
                        $st_pagamento = "<span class='badge bg-warning'>$st_pagamento</span>";
                    }

                    @endphp
                    <tr>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu" data-popper-placement="bottom-end">
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.acessar_grupo', $procedimento->codigo) }}"><i class="mdi mdi-eye me-1"></i> Acessar</a>
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.excluir_grupo', $procedimento->codigo) }}"><i class="mdi mdi-delete me-1"></i> Excluir Grupo</a>
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.imprimir_paciente', $procedimento->codigo) }}" target="_blank"><i class="mdi mdi-cloud-print me-1"></i> Imprimir Prontuário</a>
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.imprimir_cadastro', $procedimento->codigo) }}"><i class="mdi mdi-folder-open me-1"></i> Imprimir Cadastro</a>
                                    @if($st_procedimento != "Finalizado" && $st_procedimento != "Cancelado")
                                        <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.cancelar', $procedimento->codigo) }}"><i class="mdi mdi-cancel me-1"></i> Cancelar</a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td> <span style='display: none'>{{ strtotime($procedimento->data_cad) }}</span> {{ dataDbForm($procedimento->data_cad) }}</td>
                        <td>{{ $procedimento->paciente->nm_paciente }}</td>
                        <td>{{ $procedimento->codigo }}</td>
                        <td>{{ $procedimento->get_nr_semanas() }}</td>
                        <td>{{ $procedimento->medico }}</td>
                        <td>{{ dataDbForm($procedimento->data_aplicacao) }}</td>
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
    order: [[1, 'desc']],
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
