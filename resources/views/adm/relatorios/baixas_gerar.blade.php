@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Resultado do Relatório de Baixas</h4>
            <form action="{{ route('adm.relatorios.exportar_baixas') }}" method="post" target="_blank">
                @csrf
                <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
                <button type="submit" class="btn btn-label-success waves-effect">
                    <span class="tf-icons mdi mdi-file-excel me-1"></span> Exportar Excel
                </button>
            </form>
        </div>
        <hr>
        <div class="table-responsive" id="tabela_relatorio">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Clínica</th>
                        <th>Medicamento</th>
                        <th>Lote</th>
                        <th>Quantidade</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Usuário</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $total_geral = 0;
                        $totais_med = [];
                    @endphp
                    @foreach($movimentacoes as $linha)
                        @php 
                            $total_geral += $linha['quantidade'];
                            $med_nome = $linha['medicamento'];
                            $totais_med[$med_nome] = ($totais_med[$med_nome] ?? 0) + $linha['quantidade'];
                        @endphp
                        <tr>
                            <td>{{ $linha['data']->format('d/m/Y H:i') }}</td>
                            <td>{{ $linha['clinica'] }}</td>
                            <td>{{ $linha['medicamento'] }}</td>
                            <td>{{ $linha['lote'] }}</td>
                            <td>{{ number_format($linha['quantidade'], 2, ',', '.') }}</td>
                            <td>
                                @if($linha['tipo'] == 'Fechado')
                                    <span class="badge bg-label-primary">Fechado</span>
                                @else
                                    <span class="badge bg-label-info">Aberto</span>
                                @endif
                            </td>
                            <td>{{ $linha['motivo'] }}</td>
                            <td>{{ $linha['usuario'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">TOTAL GERAL:</th>
                        <th>{{ number_format($total_geral, 2, ',', '.') }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card bg-label-secondary">
                    <div class="card-body">
                        <h5 class="card-title">Resumo por Medicamento</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Total Baixado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($totais_med as $nome => $total)
                                    <tr>
                                        <td>{{ $nome }}</td>
                                        <td>{{ number_format($total, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
