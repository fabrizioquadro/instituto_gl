@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Caixa Geral</h4>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="mdi mdi-printer me-1"></i> Imprimir
            </button>
        </div>
        <div class="mt-2">
            <strong>Período:</strong> {{ dataDbForm($dados['dt_inc']) }} até {{ dataDbForm($dados['dt_fn']) }} <br>
            <strong>Colaborador(a):</strong> {{ $user_filtro ? $user_filtro->nome : 'Todos' }}
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Data/Hora</th>
                        <th>Colaborador</th>
                        <th>Paciente</th>
                        <th>Valor Recebido</th>
                        <th>Forma de Pagamento</th>
                        <th>Nº DOC</th>
                        <th>Desconto Aplicado</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @foreach($pagamentos as $pagamento)
                        <tr>
                            <td>{{ date('d/m/Y H:i:s', strtotime($pagamento->created_at)) }}</td>
                            <td>{{ $pagamento->cadastrante ? $pagamento->cadastrante->nome : 'Não Identificado' }}</td>
                            <td>{{ $pagamento->financeiro->paciente->nm_paciente }}</td>
                            <td>R$ {{ valorDbForm($pagamento->vl_pagamento) }}</td>
                            <td>{{ $pagamento->forma_pagamento }} {{ $pagamento->parcelas > 1 ? '('.$pagamento->parcelas.'x)' : '' }}</td>
                            <td>{{ $pagamento->id_pagamento }}</td>
                            <td>R$ {{ valorDbForm($pagamento->financeiro->vl_desconto) }}</td>
                        </tr>
                        @php $total += $pagamento->vl_pagamento; @endphp
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">TOTAL GERAL</th>
                        <th>R$ {{ valorDbForm($total) }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="row mt-5 pt-5">
            <div class="col-6 text-center">
                <hr style="width: 80%; margin: auto; border-top: 1px solid #000 !important;">
                <p>Assinatura do Colaborador (Entrega)</p>
            </div>
            <div class="col-6 text-center">
                <hr style="width: 80%; margin: auto; border-top: 1px solid #000 !important;">
                <p>Assinatura do Responsável (Recebimento)</p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .layout-menu, .layout-navbar, .footer {
        display: none !important;
    }
    .container-xxl {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
@endsection
