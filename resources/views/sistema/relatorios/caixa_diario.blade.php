@extends(session()->get('layout') == 'admin' ? 'layout.admin' : 'layout.sistema')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Caixa Diário - {{ dataDbForm(date('Y-m-d')) }}</h4>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="mdi mdi-printer me-1"></i> Imprimir
            </button>
        </div>
        <div class="mt-2">
            <strong>Colaboradora:</strong> {{ $user->nome }}
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Valor Recebido</th>
                        <th>Forma de Pagamento</th>
                        <th>Desconto Aplicado</th>
                        <th>Data/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @foreach($pagamentos as $pagamento)
                        <tr>
                            <td>{{ $pagamento->financeiro->paciente->nm_paciente }}</td>
                            <td>R$ {{ valorDbForm($pagamento->vl_pagamento) }}</td>
                            <td>{{ $pagamento->forma_pagamento }} {{ $pagamento->parcelas > 1 ? '('.$pagamento->parcelas.'x)' : '' }}</td>
                            <td>R$ {{ valorDbForm($pagamento->financeiro->vl_desconto) }}</td>
                            <td>{{ date('H:i:s', strtotime($pagamento->created_at)) }}</td>
                        </tr>
                        @php $total += $pagamento->vl_pagamento; @endphp
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>TOTAL</th>
                        <th>R$ {{ valorDbForm($total) }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
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
