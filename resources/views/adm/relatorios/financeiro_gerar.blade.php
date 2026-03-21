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
            <h4 class="card-title">Relatório Financeiro</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="tabela-index table" id="">
                <thead class="table-light">
                    <tr>
                        <td>ID</td>
                        <td>Data</td>
                        <td>Paciente</td>
                        <td>CPF</td>
                        <td>Codigo</td>
                        <td>Pagamento</td>
                        <td>Valor</td>
                        <td>Tipo</td>
                        <td>Desconto</td>
                        <td>Forma Pagamento</td>
                        <td>Parcelas</td>
                        <td>Clinica</td>
                        <td>Médico</td>
                        <td>Nr Procedimentos</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($array_financeiro as $linha)
                        <tr>
                            <td> <a href="{{ route('sistema.financeiros.acessar', $linha['financeiro_id']) }}" target="_blank"> {{ $linha['pagamento_id'] }} </a></td>
                            <td>{{ $linha['data'] }}</td>
                            <td>{{ $linha['paciente'] }}</td>
                            <td>{{ $linha['cpf'] }}</td>
                            <td> <a href="{{ route('sistema.procedimentos.acessar_grupo', $linha['codigo']) }}" target="_blank"> {{ "'$linha[codigo]'" }} </a></td>
                            <td>{{ $linha['vl_pagamento'] }}</td>
                            <td>{{ $linha['vl_rateio'] }}</td>
                            <td>{{ $linha['tp_pagamento'] }}</td>
                            <td>{{ $linha['desconto'] }}</td>
                            <td>{{ $linha['forma_pagamento'] }}</td>
                            <td>{{ $linha['parcelas'] }}</td>
                            <td>{{ $linha['clinica'] }}</td>
                            <td>{{ $linha['medico'] }}</td>
                            <td>{{ $linha['contador'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<form target="_blank" id='formulario' action="{{ route('adm.relatorios.exportar') }}" method="post">
    @csrf
    <input type="hidden" name="data" id="data">
</form>
<script>
document.getElementById('exportar').addEventListener('click', ()=>{
    dados = document.getElementById('div_dados').innerHTML;
    document.getElementById('data').value = dados;
    document.getElementById('formulario').submit();
})

</script>
@endsection
