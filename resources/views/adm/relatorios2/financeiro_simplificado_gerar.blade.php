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
            <h4 class="card-title">Relatório Financeiro Simplificado (Prescrições) - Resultados</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="tabela-index table table-sm">
                <thead class="table-light">
                    <tr>
                        <td>ID</td>
                        <td>Data</td>
                        <td>Paciente</td>
                        <td>ID Feegow</td>
                        <td>CPF</td>
                        <td>Código</td>
                        <td>Valor Tratamento</td>
                        <td>Crédito em Aberto</td>
                        <td>Pagamento</td>
                        <td>Procedimentos</td>
                        <td>Aplicações</td>
                        <td>Atendimento</td>
                        <td>Forma Pagamento</td>
                        <td>ID Pagamento</td>
                        <td>Parcelas</td>
                        <td>Clínica</td>
                        <td>Médico</td>
                        <td>Quem recebeu</td>
                        <td>Situação Fin.</td>
                        <td>Obs</td>
                    </tr>
                </thead>
                <tbody>
                    @php $total_pagamento = 0; $total_procedimentos = 0; $total_aplicacoes = 0; @endphp
                    @foreach($array_financeiro as $linha)
                        <tr>
                            <td>
                                <a href="{{ route('sistema.prescricoes.financeiro', $linha['prescricao_id']) }}" target="_blank">
                                    {{ $linha['pagamento_id'] }}
                                </a>
                            </td>
                            <td>{{ $linha['data'] }}</td>
                            <td>{{ $linha['paciente'] }}</td>
                            <td>{{ $linha['id_feegow'] }}</td>
                            <td>{{ $linha['cpf'] }}</td>
                            <td>
                                <a href="{{ route('sistema.prescricoes.acessar', $linha['prescricao_id']) }}" target="_blank">
                                    {{ $linha['codigo'] }}
                                </a>
                            </td>
                            <td>{{ $linha['vl_tratamento'] }}</td>
                            <td>{{ $linha['credito_total'] }}</td>
                            <td>{{ $linha['vl_pagamento'] }}</td>
                            <td>{{ $linha['vl_procedimentos'] }}</td>
                            <td>{{ $linha['vl_aplicacoes'] }}</td>
                            <td>{{ $linha['tipo_atendimento'] }}</td>
                            <td>{{ $linha['forma_pagamento'] }}</td>
                            <td>{{ $linha['id_pagamento'] }}</td>
                            <td>{{ $linha['parcelas'] }}</td>
                            <td>{{ $linha['clinica'] }}</td>
                            <td>{{ $linha['medico'] }}</td>
                            <td>{{ $linha['colaborador'] }}</td>
                            <td>{{ $linha['situacao_financeira'] }}</td>
                            <td>{{ $linha['obs'] }}</td>
                        </tr>
                        @php
                            $total_pagamento += (float) str_replace(['R$ ', '.', ','], ['', '', '.'], $linha['vl_pagamento']);
                            $total_procedimentos += (float) str_replace(['R$ ', '.', ','], ['', '', '.'], $linha['vl_procedimentos']);
                            $total_aplicacoes += (float) str_replace(['R$ ', '.', ','], ['', '', '.'], $linha['vl_aplicacoes']);
                        @endphp
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="8" class="text-end">TOTAL GERAL</th>
                        <th>R$ {{ valorDbForm($total_pagamento) }}</th>
                        <th>R$ {{ valorDbForm($total_procedimentos) }}</th>
                        <th>R$ {{ valorDbForm($total_aplicacoes) }}</th>
                        <th colspan="9"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<form target="_blank" id='formulario' action="{{ route('adm.relatorios2.exportar_financeiro_simplificado') }}" method="post">
    @csrf
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>

<script>
document.getElementById('exportar').addEventListener('click', () => {
    document.getElementById('formulario').submit();
});
</script>
@endsection
