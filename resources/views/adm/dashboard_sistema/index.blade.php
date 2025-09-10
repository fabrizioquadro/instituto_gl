@extends('layout.admin')

@section('conteudo')
<style media="screen">
    .select2-selection__rendered{
        line-height: 40px !important;
        border-color: red !important;
    }
    .select2-selection{
        height: 40px !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Secretaria</h4>
            <a href="{{ route('sistema.procedimentos.adicionar','adm_dashboard') }}" class="btn btn-primary">Adicionar Procedimento</a>
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
        <form action="{{ route('adm.sistema.dashboard') }}" method="post">
            @csrf
            <div class="row gy-4 align-items-end">
                <div class="col-md-8">
                    <div class="form-floating form-floating-outline">
                        <select required id="paciente_id" name='paciente_id' class="select2 form-select">
                            <option value="">Opções</option>
                        </select>
                        <label for="paciente_id">Paciente:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">Pesquisar</button>
                </div>
            </div>
        </form>
        <hr>
        <h6 class="card-title">Procedimentos</h6>
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Cad</th>
                        <th>Aplicação</th>
                        <th>Pagamento</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($procedimentos_sec as $procedimento)
                        <tr style="cursor: pointer" onclick="acessa_procedimento({{ $procedimento->id }})">
                            <td>{{ $procedimento->paciente->nm_paciente }}</td>
                            <td>{{ $procedimento->medico }}</td>
                            <td>{{ dataDbForm($procedimento->data_cad) }}</td>
                            <td>{{ dataDbForm($procedimento->data_aplicacao) }}</td>
                            <td>{{ $procedimento->st_pagamento }}</td>
                            <td>{{ valorDbForm($procedimento->valor) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">

function acessa_procedimento(procedimento_id){
    window.location.href = "/sistema/procedimentos/acessar/" + procedimento_id + "/adm_dashboard";
}

window.addEventListener('load',()=>{
    $('.combobox').combobox();

    $('#paciente_id').select2({
        placeholder: "Escolha o Paciente.",
        allowClear: true,
        minimumInputLength: 2,
        ajax:{
            url:"{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
            dataType: "json",
            type: 'GET',
            delay: 250,
            data:function(params){
                return {
                    q: params.term,
                };
            },
            processResults: function(data){
                return {
                    results:data
                };
            },
        cache: true
        }
    });
});
</script>
<hr>
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
                <h5 class="card-title">Lista de Espera de Aplicações</h5>
                <div class="table-responsive">
                    <table class="tabela-index table" id="table-index">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos as $procedimento)
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu" data-popper-placement="bottom-end">
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Acessar</a>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $procedimento->paciente->nm_paciente }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{{ $procedimento->situacao }}</td>
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
