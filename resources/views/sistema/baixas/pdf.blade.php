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
        font-size: 12px;
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
    Relatório de Baixa - #{{ $baixa->id }}
</div>

<div class="info">
    <table>
        <tr>
            <td><b>Data:</b> {{ dataDbForm($baixa->data) }}</td>
            <td><b>Usuário Responsável:</b> 
                @if($baixa->user)
                    {{ $baixa->user->nome }} ({{ $baixa->user->email }})
                @else
                    Não identificado
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2"><b>Motivo:</b> {{ $baixa->motivo }}</td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th style='width: 50%'>Medicamento</th>
            <th>Lote</th>
            <th>Quantidade</th>
        </tr>
    </thead>
    <tbody>
        @foreach($baixa->medicamentos as $estoque)
            <tr>
                <td>{{ $estoque->medicamento->nome }}</td>
                <td>{{ $estoque->lote }}</td>
                <td>{{ $estoque->quantidade }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
