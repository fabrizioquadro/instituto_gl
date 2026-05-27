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
            <h4 class="card-title">Relatório Estoque</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar Excel</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-sm table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Clínica</th>
                        <th>Medicamento</th>
                        <th>C. Barras</th>
                        <th>Lote</th>
                        <th>Vencimento</th>
                        <th>Saldo Estoque</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultados as $linha)
                        @php
                            $vencimento = \Carbon\Carbon::parse($linha['dt_vencimento']);
                            $hoje = \Carbon\Carbon::now();
                            $dias = $hoje->diffInDays($vencimento, false);
                            $classe = '';
                            if($dias < 0) {
                                $classe = 'text-danger fw-bold';
                            } elseif($dias <= 30) {
                                $classe = 'text-warning fw-bold';
                            }
                        @endphp
                        <tr>
                            <td>{{ $linha['clinica'] }}</td>
                            <td>{{ $linha['medicamento'] }}</td>
                            <td>{{ $linha['codigo_barras'] }}</td>
                            <td>{{ $linha['lote'] }}</td>
                            <td class="{{ $classe }}">
                                {{ dataDbForm($linha['dt_vencimento']) }}
                                @if($dias < 0)
                                    <span class="badge bg-danger ms-2">Vencido</span>
                                @elseif($dias <= 30)
                                    <span class="badge bg-warning ms-2">Vence em {{ $dias }} dias</span>
                                @endif
                            </td>
                            <td><strong>{{ $linha['saldo'] }}</strong></td>
                        </tr>
                    @endforeach
                    @if(count($resultados) == 0)
                        <tr>
                            <td colspan="6" class="text-center">Nenhum estoque encontrado com saldo positivo para os filtros selecionados.</td>
                        </tr>
                    @endif
                </tbody>
            </table>

        </div>
    </div>
</div>
<form target="_blank" id='formulario' action="{{ route('adm.relatorios.exportar_estoque') }}" method="post">
    @csrf
    <input type="hidden" name="data" id="data">
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>
<script>
document.getElementById('exportar').addEventListener('click', ()=>{
    document.getElementById('formulario').submit();
})
</script>
@endsection
