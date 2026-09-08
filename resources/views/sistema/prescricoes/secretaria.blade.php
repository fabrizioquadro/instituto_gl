@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)
@section('conteudo')
<style>
/* dropdown não pode ser cortado pelo .table-responsive — libera o overflow ao abrir */
.table-responsive.dash-dropdown:has(.dropdown-menu.show) {
    overflow: visible !important;
}
.dash-dropdown .dropdown-menu {
    z-index: 1060 !important;
}
/* compactar tabelas DataTables (o CDN sobrescreve o padding do table-sm) */
.table.table-sm.dataTable thead th,
.table.table-sm.dataTable thead td,
.table.table-sm.dataTable tbody th,
.table.table-sm.dataTable tbody td {
    padding: 0.3125rem 0.625rem !important;
    white-space: nowrap !important;
}
.select2-selection__rendered{
    line-height: 40px !important;
}
.select2-selection{
    height: 40px !important;
}
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Secretaria</h4>
            <div>
                <a href="{{ route('sistema.prescricoes.bio_coleta') }}" class="btn btn-outline-primary waves-effect">
                    <span class="tf-icons mdi mdi-test-tube me-1"></span>Bio/Coleta
                </a>
                <a href="{{ route('sistema.prescricoes.adicionar') }}" class="btn btn-primary">
                    <span class="tf-icons mdi mdi-plus me-1"></span>Adicionar
                </a>
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

        {{-- BUSCA POR PACIENTE (procedimentos/prescrições do paciente) --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title mb-2">Buscar Procedimentos do Paciente</h5>
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="secretaria_paciente_id">Paciente:</label>
                        <select id="secretaria_paciente_id" class="select2 form-select" style="width:100%">
                            <option value=""></option>
                        </select>
                    </div>
                </div>
                <div id="card_prescricoes_paciente" class="mt-3 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Prescrições do Paciente</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpar_busca_paciente()">Limpar busca</button>
                    </div>
                    <div class="table-responsive">
                        <table class="tabela-index table table-sm nowrap" id="table-prescricoes-paciente">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Dt Prescrição</th>
                                    <th>Paciente</th>
                                    <th>Médico</th>
                                    <th>Tipo Atendimento</th>
                                    <th>Semana</th>
                                    <th>Situação</th>
                                    <th>Sit. Financeira</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 1 — AGENDADOS PARA HOJE --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title mb-2">Agendados para Hoje
                    <span class="badge rounded-pill bg-label-primary ms-1">{{ $totais['agendados'] }}</span>
                </h5>
                <div class="table-responsive dash-dropdown">
                    <table class="tabela-index table table-sm nowrap" id="table-agendados">
                        <thead class="table-light">
                            <tr>
                                <th>Paciente</th>
                                <th>Prescrição</th>
                                <th>Semana(s)</th>
                                <th>Data Prevista</th>
                                <th>Medicações</th>
                                <th>Médico</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- CARD 2 — ATRASADOS --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title mb-2">Atrasados
                    <span class="badge rounded-pill bg-label-warning ms-1">{{ $totais['atrasados'] }}</span>
                </h5>
                <div class="table-responsive dash-dropdown">
                    <table class="tabela-index table table-sm nowrap" id="table-atrasados">
                        <thead class="table-light">
                            <tr>
                                <th>Paciente</th>
                                <th>Prescrição</th>
                                <th>Semana(s)</th>
                                <th>Data Prevista</th>
                                <th>Dias Atraso</th>
                                <th>Medicações</th>
                                <th>Médico</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- CARD 3 — APLICAÇÕES DO DIA + FILA/ATENDIMENTO AGORA --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title mb-2">Aplicações do Dia
                    <span class="badge rounded-pill bg-label-success ms-1">{{ $totais['aplicacoes'] }}</span>
                </h5>
                <div class="table-responsive dash-dropdown">
                    <table class="tabela-index table table-sm nowrap" id="table-aplicacoes">
                        <thead class="table-light">
                            <tr>
                                <th>Paciente</th>
                                <th>Prescrição</th>
                                <th>Semana(s)</th>
                                <th>Situação</th>
                                <th>Medicações</th>
                                <th>Médico</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let idioma_datatables = {
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
};

function initSecretariaTable(id, card){
    $('#' + id).DataTable({
        "processing": true,
        "serverSide": true,
        "ordering": false,
        "ajax": {
            "url": "{{ route('sistema.secretaria.pesq') }}",
            "type": "GET",
            "data": function(d){ d.card = card; }
        },
        "language": idioma_datatables,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        "pageLength": 10
    });
}

let tabela_prescricoes_paciente = null;
let paciente_id_selecionado = null;

function montar_tabela_prescricoes_paciente(){
    if(tabela_prescricoes_paciente){
        tabela_prescricoes_paciente.ajax.reload();
        return;
    }
    tabela_prescricoes_paciente = $('#table-prescricoes-paciente').DataTable({
        "processing": true,
        "serverSide": true,
        "ordering": false,
        "ajax": {
            "url": "{{ route('sistema.secretaria.prescricoes_paciente_pesq') }}",
            "type": "GET",
            "data": function(d){ d.paciente_id = paciente_id_selecionado; }
        },
        "language": idioma_datatables,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        "pageLength": 10
    });
}

function limpar_busca_paciente(){
    paciente_id_selecionado = null;
    $('#secretaria_paciente_id').val(null).trigger('change');
    $('#card_prescricoes_paciente').addClass('d-none');
}

window.addEventListener('load', function(){
    initSecretariaTable('table-agendados', 'agendados');
    initSecretariaTable('table-atrasados', 'atrasados');
    initSecretariaTable('table-aplicacoes', 'aplicacoes');

    // busca de paciente (mesmo ajax do cadastro de prescrição)
    if(typeof $ !== 'undefined'){
        $('#secretaria_paciente_id').select2({
            placeholder: 'Digite o nome do paciente...',
            allowClear: true,
            minimumInputLength: 2,
            ajax:{
                url: "{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
                dataType: "json",
                type: 'GET',
                delay: 250,
                data: function(params){ return { q: params.term }; },
                processResults: function(data){ return { results: data }; },
                cache: true
            }
        });
        $('#secretaria_paciente_id').on('select2:select', function (e) {
            var data = e.params.data;
            paciente_id_selecionado = data.id;
            $('#card_prescricoes_paciente').removeClass('d-none');
            montar_tabela_prescricoes_paciente();
        });
        $('#secretaria_paciente_id').on('select2:clear', function(){
            limpar_busca_paciente();
        });
    }
});
</script>
@endsection
