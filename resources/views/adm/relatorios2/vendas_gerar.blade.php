@extends('layout.admin')

@section('conteudo')
<style media="screen">
    td a {
        color: #828393;
        text-decoration: none;
    }
</style>
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Vendas (Prescrições) - Resultados</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Medicamento</th>
                        <th>Combo</th>
                        <th>Quantidade</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th>Semana</th>
                        <th>Previsão</th>
                        <th>Aplicação</th>
                        <th>Valor Semana</th>
                        <th>Pago Semana</th>
                        <th>Situação Fin.</th>
                        <th>Último Pagamento</th>
                        <th>Prescrição</th>
                        <th>Paciente</th>
                        <th>Clínica</th>
                        <th>Médico</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itens as $item)
                        @php
                            $semana = $item->semana;
                            $prescricao = $semana->prescricao ?? null;
                            $parcela = $semana->parcela ?? null;
                        @endphp
                        <tr>
                            <td>{{ $item->medicamento->nome ?? 'N/A' }}</td>
                            <td>{{ $item->combo->nome ?? '' }}</td>
                            <td>{{ valorDbForm($item->quantidade) }}</td>
                            <td>{{ $item->situacao }}</td>
                            <td>{{ dataDbForm($prescricao->data_prescricao ?? null) }}</td>
                            <td>{{ $semana->nr_semana ?? '' }}</td>
                            <td>{{ dataDbForm($semana->data_prevista ?? null) }}</td>
                            <td>
                                {{ dataDbForm($item->aplicado_em ?: ($semana->data_aplicada ?? null)) }}
                                @if($item->aplicado_em)
                                    {{ explode(' ', $item->aplicado_em)[1] ?? '' }}
                                @endif
                            </td>
                            <td>R$ {{ valorDbForm($parcela->valor_parcela ?? 0) }}</td>
                            <td>R$ {{ valorDbForm($parcela->valor_pago ?? 0) }}</td>
                            <td>{{ $prescricao->situacao_financeira ?? '' }}</td>
                            <td>{{ dataDbForm($prescricao->dt_ultimo_pagamento ?? null) }}</td>
                            <td>
                                @if($prescricao)
                                    <a href="{{ route('sistema.prescricoes.acessar', $prescricao->id) }}" target="_blank">
                                        {{ $prescricao->codigo_versao1 ?: $prescricao->id }}
                                    </a>
                                @endif
                            </td>
                            <td>{{ $prescricao->paciente->nm_paciente ?? 'N/A' }}</td>
                            <td>{{ $prescricao->clinica->nome ?? 'N/A' }}</td>
                            <td>{{ $prescricao->medico ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<form target="_blank" id='formulario' action="{{ route('adm.relatorios2.exportar_vendas') }}" method="post">
    @csrf
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>

<script>
document.getElementById('exportar').addEventListener('click', () => {
    document.getElementById('formulario').submit();
});
</script>
@endsection
