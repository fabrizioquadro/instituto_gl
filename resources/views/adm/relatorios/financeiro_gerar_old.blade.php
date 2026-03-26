@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório Financeiro</h4>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Paciente</th>
                        <th>Codigo</th>
                        <th>Vl Consulta</th>
                        <th>Vl Desconto</th>
                        <th>Vl Procedimentos</th>
                        <th>Valor Pago</th>
                        <th>Forma Pgt</th>
                        <th>Parcelas</th>
                        <th>Clinica</th>
                        <th>Médico</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($financeiros as $financeiro)
                        @php
                        $procedimento = $financeiro->procedimentos()->first();
                        @endphp
                        @foreach($financeiro->formas as $forma)
                            @php
                            $var = explode(" ",$forma->created_at);
                            $data = dataDbForm($var[0]);
                            @endphp
                            <tr>
                                <td>{{ $data }}</td>
                                <td>{{ $financeiro->paciente->nm_paciente }}</td>
                                <td>{{ $procedimento ? $procedimento->codigo : '' }}</td>
                                <td>R$ {{ valorDbForm($financeiro->vl_consulta) }}</td>
                                <td>R$ {{ valorDbForm($financeiro->vl_desconto) }}</td>
                                <td>R$ {{ valorDbForm($financeiro->vl_procedimentos) }}</td>
                                <td>R$ {{ valorDbForm($forma->vl_pagamento) }}</td>
                                <td>{{ $forma->forma_pagamento }}</td>
                                <td>{{ $forma->parcelas }}</td>
                                <td>{{ $financeiro->clinica->nome }}</td>
                                <td>{{ $financeiro->medico }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
