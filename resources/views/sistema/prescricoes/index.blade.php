@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Prescrições</h4>
            <a href="{{ route('sistema.prescricoes.adicionar') }}" class="btn btn-primary">Adicionar</a>
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
            <table class="tabela-index table table-sm nowrap" id="table-index">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Dt Prescrição</th>
                        <th>Paciente</th>
                        <th>Nascimento</th>
                        <th>Semana</th>
                        <th>Próx. Aplicação</th>
                        <th>Médico</th>
                        <th>Atendimento</th>
                        <th>Valor</th>
                        <th>Situação</th>
                        <th>Situação Financeira</th>
                        <th>Cadastrante</th>
                        <th>Última Edição</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<style>
/* O CSS do DataTables (CDN) sobrescreve o padding do table-sm */
.table.table-sm.dataTable thead th,
.table.table-sm.dataTable thead td,
.table.table-sm.dataTable tbody th,
.table.table-sm.dataTable tbody td {
    padding: 0.3125rem 0.625rem !important;
    white-space: nowrap !important;
}
</style>
<script>
window.addEventListener('load',()=>{
  $('#table-index').DataTable({
        "processing": true,
  		"serverSide": true,
        "ordering": false,
  		"ajax":
  		{
  			"url": "{{ route('sistema.prescricoes.index_pesq') }}",
  			"type": "GET"
  		},
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
