@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Transferências</h4>
            <a href="{{ route('sistema.transferencias.adicionar') }}" class="btn btn-primary">Adicionar</a>
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
        @if($imprimir = Session::get('imprimir_etiquetas'))
            <script>
                window.addEventListener('load', function() {
                    window.open('/sistema/entradas/etiquetas_imprimir/' + '{!! $imprimir !!}', '_blank');
                });
            </script>
        @endif
        <hr>
        <div class="table-responsive">
            <table class="tabela-index table" id="table-index">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Data</th>
                        <th>Usuário</th>
                        <th>Código de Barras</th>
                        <th>Motivo</th>
                        <th>Origem</th>
                        <th>Destino</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                @foreach($transferencias as $transferencia)
                    <tr>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu" data-popper-placement="bottom-end">
                                    @if($user->clinica_id == $transferencia->clinica_id)
                                        <a class="dropdown-item waves-effect" href="{{ route('sistema.transferencias.excluir', $transferencia->id) }}"><i class="mdi mdi-trash-can-outline me-1"></i> Excluir</a>
                                    @endif
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.transferencias.visualizar', $transferencia->id) }}"><i class="mdi mdi-eye me-1"></i> Visualizar</a>
                                </div>
                            </div>
                        </td>
                        <td> <span style='display: none'>{{ strtotime($transferencia->data) }}</span> {{ dataDbForm($transferencia->data) }}</td>
                        <td>
                            @if($transferencia->administrador)
                                {{ $transferencia->administrador->nome }}<br><small class="text-muted">{{ $transferencia->administrador->email }}</small>
                            @elseif($transferencia->user)
                                {{ $transferencia->user->nome }}<br><small class="text-muted">{{ $transferencia->user->email }}</small>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $medicamentos = $transferencia->medicamentos($user->clinica_id);
                                $codigos = [];
                                foreach($medicamentos as $med) {
                                    if($med->codigo_barras) {
                                        $nomeMed = $med->medicamento ? $med->medicamento->nome : 'Medicamento';
                                        $codigos[] = $nomeMed . ' (' . $med->codigo_barras . ')';
                                    }
                                }
                                $codigos_str = implode('<br>', array_unique($codigos));
                            @endphp
                            {!! $codigos_str !!}
                        </td>
                        <td>{{ $transferencia->motivo }}</td>
                        <td>{{ $transferencia->origem->nome }}</td>
                        <td>{{ $transferencia->destino->nome }}</td>
                        <td>{{ valorDbForm($transferencia->valor) }}</td>
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
