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
    Relatório de Transferência - #{{ $transferencia->id }}
</div>

<div class="info">
    <table>
        <tr>
            <td><b>Clínica Origem:</b> {{ $transferencia->origem->nome }}</td>
            <td><b>Clínica Destino:</b> {{ $transferencia->destino->nome }}</td>
        </tr>
        <tr>
            <td><b>Data:</b> {{ dataDbForm($transferencia->data) }}</td>
            <td><b>Usuário Responsável:</b> 
                @if($transferencia->administrador)
                    {{ $transferencia->administrador->nome }} ({{ $transferencia->administrador->email }})
                @elseif($transferencia->user)
                    {{ $transferencia->user->nome }} ({{ $transferencia->user->email }})
                @else
                    Não identificado
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2"><b>Motivo:</b> {{ $transferencia->motivo }}</td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th style='width: 40%'>Medicamento</th>
            <th>Lote</th>
            <th>C. Barras</th>
            <th>Quantidade</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transferencia->medicamentos($user->clinica_id) as $linha)
            <tr>
                <td>{{ $linha->medicamento->nome }}</td>
                <td>{{ $linha->lote }}</td>
                <td>{{ $linha->codigo_barras }}</td>
                <td>{{ $linha->quantidade }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
