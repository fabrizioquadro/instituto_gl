@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

@php
switch($prescricao->situacao){
    case 'Agendada': $badge_situacao = 'bg-label-warning'; break;
    case 'Em Andamento': $badge_situacao = 'bg-label-info'; break;
    case 'Concluída': $badge_situacao = 'bg-label-success'; break;
    case 'Cancelada': $badge_situacao = 'bg-label-danger'; break;
    default: $badge_situacao = 'bg-label-secondary';
}
switch($prescricao->situacao_financeira){
    case 'Pago': $badge_fin = 'bg-success'; break;
    case 'Parcial': $badge_fin = 'bg-warning'; break;
    case 'Em Aberto': $badge_fin = 'bg-danger'; break;
    default: $badge_fin = 'bg-secondary';
}
@endphp

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Prescrição #{{ $prescricao->id }}</h4>
            <div>
                <a href="{{ route('sistema.prescricoes.editar_prescricao', $prescricao->id) }}" class="btn btn-outline-secondary btn-sm">
                    <span class="tf-icons mdi mdi-pencil me-1"></span> Editar
                </a>
                <a href="{{ route('sistema.prescricoes.imprimir_paciente', $prescricao->id) }}" target="_blank" class="btn btn-outline-info btn-sm">
                    <span class="tf-icons mdi mdi-cloud-print me-1"></span> Imprimir Prontuário
                </a>
                <a href="{{ route('sistema.prescricoes.imprimir_cadastro', $prescricao->id) }}" target="_blank" class="btn btn-outline-success btn-sm">
                    <span class="tf-icons mdi mdi-folder-open me-1"></span> Imprimir Cadastro
                </a>
                <a href="{{ route('sistema.prescricoes.imprimir_detalhes', $prescricao->id) }}" target="_blank" class="btn btn-outline-warning btn-sm">
                    <span class="tf-icons mdi mdi-printer me-1"></span> Imprimir Detalhes
                </a>
                <a href="{{ route('sistema.prescricoes.financeiro', $prescricao->id) }}" class="btn btn-outline-primary btn-sm">
                    <span class="tf-icons mdi mdi-cash me-1"></span> Financeiro Completo
                </a>
                <a href="{{ route('sistema.prescricoes') }}" class="btn btn-outline-dark btn-sm">Voltar</a>
            </div>
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

        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="w-25">Paciente</th>
                        <td><b>{{ $prescricao->paciente->nm_paciente ?? '-' }}</b></td>
                    </tr>
                    <tr>
                        <th>Código</th>
                        <td>{{ $prescricao->codigo_versao1 ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Data Prescrição</th>
                        <td>{{ $prescricao->data_prescricao ? dataDbForm($prescricao->data_prescricao) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Médico</th>
                        <td>{{ $prescricao->medico ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Tipo Atendimento</th>
                        <td>{{ $prescricao->tipo_atendimento ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Clínica</th>
                        <td>{{ $prescricao->clinica->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Cadastrante</th>
                        <td>{{ $prescricao->userCadastro->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Obs</th>
                        <td>{{ $prescricao->obs ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="w-25">Qt Semanas</th>
                        <td>{{ $prescricao->qt_semanas }}</td>
                    </tr>
                    <tr>
                        <th>Semanas c/ Aplicação</th>
                        <td>{{ $prescricao->qt_semanas_aplicacao }}</td>
                    </tr>
                    <tr>
                        <th>Semana Atual</th>
                        <td>{{ $prescricao->semana_atual > 0 ? $prescricao->semana_atual : 'Não Iniciado' }}</td>
                    </tr>
                    <tr>
                        <th>Valor Tratamento</th>
                        <td>R$ {{ number_format($prescricao->valor_tratamento, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Total Pago</th>
                        <td>R$ {{ number_format($prescricao->total_pago, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Crédito em Aberto</th>
                        <td>R$ {{ number_format($prescricao->credito_em_aberto, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Situação</th>
                        <td><span class="badge rounded-pill {{ $badge_situacao }}">{{ $prescricao->situacao }}</span></td>
                    </tr>
                    <tr>
                        <th>Situação Financeira</th>
                        <td><span class="badge rounded-pill {{ $badge_fin }}">{{ $prescricao->situacao_financeira }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title mb-0">Semanas</h4>
            <div>
                <a href="{{ route('sistema.prescricoes.adicionar_medicamentos', $prescricao->id) }}" class="btn btn-outline-dark btn-sm">+ Medicamentos</a>
                <a href="{{ route('sistema.prescricoes.adicionar_semana', $prescricao->id) }}" class="btn btn-primary btn-sm">+ Semanas</a>
            </div>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="tabela-index table table-sm nowrap" id="table-semanas">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Semana</th>
                        <th>Dt Prevista</th>
                        <th>Dt Aplicada</th>
                        <th>Aplicação</th>
                        <th>Medicações</th>
                        <th>Situação</th>
                        <th>Pagamento</th>
                        <th>Obs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescricao->semanas as $semana)
                        @php
                        switch($semana->situacao){
                            case 'Agendada': $badge = 'bg-label-warning'; break;
                            case 'Fila de Aplicação': $badge = 'bg-label-primary'; break;
                            case 'Em Atendimento': $badge = 'bg-label-primary'; break;
                            case 'Aplicada': $badge = 'bg-label-success'; break;
                            case 'Aplicação Parcial': $badge = 'bg-label-warning'; break;
                            case 'Cancelada': $badge = 'bg-label-danger'; break;
                            default: $badge = 'bg-label-secondary';
                        }
                        @endphp
                        <tr>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="true">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.acessar_semana', $semana->id) }}"><i class="mdi mdi-eye me-1"></i> Acessar</a>
                                        @if($semana->situacao != 'Cancelada')
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.editar_semana', $semana->id) }}"><i class="mdi mdi-pencil me-1"></i> Editar</a>
                                            @if(session()->has('administrador') && !in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial', 'Em Atendimento']) && !$semana->medicamentos->where('situacao', 'Aplicada')->count())
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.excluir_semana', $semana->id) }}"><i class="mdi mdi-trash-can-outline me-1"></i> Excluir</a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="fw-medium">{{ $semana->nr_semana }}</td>
                            <td>{{ $semana->data_prevista ? dataDbForm($semana->data_prevista) : '-' }}</td>
                            <td>
                                @if($semana->data_aplicada)
                                    {{ dataDbForm($semana->data_aplicada) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($semana->tem_aplicacao)
                                    <span class="badge bg-info">c/ aplicação</span>
                                @else
                                    <span class="badge bg-label-secondary">sem aplicação</span>
                                @endif
                            </td>
                            <td>
                                @if($semana->medicamentos->count())
                                    @foreach($semana->medicamentos as $med)
                                        @php
                                        $st_med = $med->situacao;
                                        $badge_med = 'bg-label-secondary';
                                        if($st_med == 'Aplicada'){ $badge_med = 'bg-label-success'; }
                                        elseif($st_med == 'Aberta'){ $badge_med = 'bg-label-warning'; }
                                        elseif($st_med == 'Cancelada'){ $badge_med = 'bg-label-danger'; }
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $med->medicamento->nome ?? '?' }} @if($med->is_soro) <span class="badge bg-info ms-1">soro</span> @endif <small class="text-muted">({{ $med->quantidade }})</small></span>
                                            <span class="badge rounded-pill {{ $badge_med }}">{{ $st_med }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td><span class="badge rounded-pill {{ $badge }}">{{ $semana->situacao }}</span></td>
                            <td>
                                @if($semana->parcela)
                                    @php
                                    $par = $semana->parcela;
                                    $badge_pag = 'bg-danger';
                                    if($par->situacao == 'Paga'){ $badge_pag = 'bg-success'; }
                                    elseif($par->situacao == 'Parcial'){ $badge_pag = 'bg-warning'; }
                                    elseif($par->situacao == 'Cancelada'){ $badge_pag = 'bg-secondary'; }
                                    @endphp
                                    <span class="badge rounded-pill {{ $badge_pag }}">{{ $par->situacao }}</span>
                                    <small class="text-muted d-block">R$ {{ number_format($par->valor_pago, 2, ',', '.') }} / R$ {{ number_format($par->valor_parcela, 2, ',', '.') }}</small>
                                @else
                                    <span class="text-muted">sem parcela</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $semana->obs ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
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
}
</style>
<script>
window.addEventListener('load',()=>{
  $('#table-semanas').DataTable({
    order: [[0, 'asc']],
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
