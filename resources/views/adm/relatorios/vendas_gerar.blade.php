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
            <h4 class="card-title">Relatório Vendas</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidada</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th>Aplicação</th>
                        <th>Valor</th>
                        <th>Pago</th>
                        <th>Procedimento</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($procedimentos as $procedimento)
                        @foreach($procedimento->aplicacaos as $aplicacao)
                            @if((!$medicamento_id || $aplicacao->medicamento->id == $medicamento_id) && (!$situacao || $situacao == $aplicacao->situacao))
                                <tr>
                                    <td>{{ $aplicacao->medicamento->nome }}</td>
                                    <td>{{ $aplicacao->quantidade }}</td>
                                    <td>{{ $aplicacao->situacao }}</td>
                                    <td>{{ dataDbForm($procedimento->data_cad) }}</td>
                                    <td>{{ dataDbForm($procedimento->data_aplicacao) }}</td>
                                    <td>R$ {{ valorDbForm($aplicacao->total) }}</td>
                                    <td>{{ $procedimento->st_pagamento }}</td>
                                    <td> <a href="{{ route('sistema.procedimentos.acessar', $procedimento->id) }}" target="_blank"> {{ $procedimento->codigo."/".$procedimento->nr_procedimento }} </a></td>
                                    <td>{{ $procedimento->paciente->nm_paciente }}</td>
                                    <td>{{ $procedimento->medico }}</td>
                                </tr>
                            @endif
                        @endforeach
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
</div>
<form target="_blank" id='formulario' action="{{ route('adm.relatorios.exportar_vendas') }}" method="post">
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
