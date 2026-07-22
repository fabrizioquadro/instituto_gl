<style>
    body {
        font-family: Arial, Helvetica, sans-serif;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        border: 1px solid #000;
        padding: 5px;
        text-align: left;
        font-size: 11px;
    }
    th {
        background-color: #f2f2f2;
    }
    .header {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 20px;
    }
    .info {
        font-size: 12px;
        margin-bottom: 20px;
    }
    .info table {
        border: none;
        margin-top: 0;
    }
    .info td {
        border: none;
        padding: 2px;
    }
</style>

<div class="header">
    Relatório de Entrada - #{{ $entrada->id }}
</div>

<div class="info">
    <table>
        <tr>
            <td><b>Fornecedor:</b> {{ $entrada->fornecedor->nome }}</td>
            <td><b>Data:</b> {{ dataDbForm($entrada->data) }}</td>
        </tr>
        <tr>
            <td><b>Nr. Nota:</b> {{ $entrada->nota }}</td>
            <td><b>Valor Total:</b> R$ {{ valorDbForm($entrada->valor) }}</td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th style='width: 30%'>Medicamento</th>
            <th>Quantidade</th>
            <th>Unitário</th>
            <th>Total</th>
            <th>Lote</th>
            <th>Vencimento</th>
            <th>C. Barras</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entrada->medicamentos as $estoque)
            <tr>
                <td>{{ $estoque->medicamento->nome }}</td>
                <td>{{ $estoque->quantidade }}</td>
                <td>R$ {{ valorDbForm($estoque->valor) }}</td>
                <td>R$ {{ valorDbForm($estoque->total) }}</td>
                <td>{{ $estoque->lote }}</td>
                <td>{{ dataDbForm($estoque->dt_vencimento) }}</td>
                <td>{{ $estoque->codigo_barras }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
