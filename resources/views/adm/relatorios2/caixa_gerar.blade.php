@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Caixa (Prescrições) - Resultados</h4>
            <div>
                <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary me-2">Exportar</button>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                    <i class="mdi mdi-printer me-1"></i> Imprimir
                </button>
            </div>
        </div>
        <div class="mt-2">
            <strong>Período:</strong> {{ dataDbForm($dados['dt_inc'] ?? null) }} até {{ dataDbForm($dados['dt_fn'] ?? null) }} <br>
            <strong>Colaborador(a):</strong> {{ $user_filtro ? $user_filtro->nome : 'Todos' }}
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Data/Hora</th>
                        <th>Colaborador</th>
                        <th>Paciente</th>
                        <th>Valor Recebido</th>
                        <th>Forma de Pagamento</th>
                        <th>Nº DOC</th>
                        <th>Clínica</th>
                        <th>Prescrição</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @foreach($formas as $forma)
                        @php
                            $pagamento = $forma->pagamento;
                            $prescricao = $pagamento->prescricao ?? null;
                        @endphp
                        <tr>
                            <td>{{ dataDbForm(explode(' ', $pagamento->created_at)[0]) }} {{ explode(' ', $pagamento->created_at)[1] ?? '' }}</td>
                            <td>{{ $pagamento->user->nome ?? 'Não Identificado' }}</td>
                            <td>{{ $prescricao->paciente->nm_paciente ?? 'N/A' }}</td>
                            <td>R$ {{ valorDbForm($forma->vl_pagamento) }}</td>
                            <td>{{ $forma->forma_pagamento }} {{ $forma->parcelas > 1 ? '('.$forma->parcelas.'x)' : '' }}</td>
                            <td>{{ $forma->id_transacao ?: $pagamento->id }}</td>
                            <td>{{ $prescricao->clinica->nome ?? 'N/A' }}</td>
                            <td>
                                @if($prescricao)
                                    <a href="{{ route('sistema.prescricoes.acessar', $prescricao->id) }}" target="_blank">
                                        {{ $prescricao->codigo_versao1 ?: $prescricao->id }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @php $total += (float) $forma->vl_pagamento; @endphp
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">TOTAL GERAL</th>
                        <th>R$ {{ valorDbForm($total) }}</th>
                        <th colspan="4"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<form target="_blank" id='formulario' action="{{ route('adm.relatorios2.exportar_caixa') }}" method="post">
    @csrf
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>

<script>
document.getElementById('exportar').addEventListener('click', () => {
    document.getElementById('formulario').submit();
});
</script>

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
