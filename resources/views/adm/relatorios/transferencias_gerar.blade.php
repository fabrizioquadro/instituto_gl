@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório Transferências</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Origem</th>
                        <th>Destino</th>
                        <th>Usuário</th>
                        <th>Medicamento</th>
                        <th>Lote</th>
                        <th>C. Barras</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transferencias as $transferencia)
                        @php
                        $medicamentos = App\Models\Estoque::where('origem','Transferencia')
                        ->where('transferencia_id', $transferencia->id)
                        ->where('tipo', 'Saida')
                        ->get();
                        @endphp
                        @foreach($medicamentos as $estoque)
                            <tr>
                                <td>{{ dataDbForm($transferencia->data) }}</td>
                                <td>{{ $transferencia->origem->nome }}</td>
                                <td>{{ $transferencia->destino->nome }}</td>
                                <td>{{ $transferencia->user->name ?? '' }}</td>
                                <td>{{ $estoque->medicamento->nome }}</td>
                                <td>{{ $estoque->lote }}</td>
                                <td>{{ $estoque->codigo_barras }}</td>
                                <td>{{ $estoque->quantidade }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
</div>
<form target="_blank" id='formulario' action="{{ route('adm.relatorios.exportar_transferencias') }}" method="post">
    @csrf
    <input type="hidden" name="data" id="data">
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>
<script>
document.getElementById('exportar').addEventListener('click', ()=>{
    dados = document.getElementById('div_dados').innerHTML;
    document.getElementById('data').value = dados;
    document.getElementById('formulario').submit();
})

</script>
@endsection
