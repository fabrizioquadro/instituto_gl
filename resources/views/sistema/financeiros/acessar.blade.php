@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Acessar Financeiro</h4>
        </div>
        @if($mensagem = Session::get('mensagem'))
            <div class="alert alert-success alert-dismissible mt-3" role="alert">
                {{ $mensagem }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($mensagem = Session::get('mensagem_erro'))
            <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                {{ $mensagem }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <hr>
        <div class="row mt-2 gy-4 align-items-end mb-3">
            <div class="col-md-4 form-group">
                <label for="">Paciente:</label><br>
                <b>{{ $financeiro->paciente->nm_paciente }}</b>
            </div>
            <div class="col-md-4 form-group">
                <label for="">Cinica:</label><br>
                <b>{{ $financeiro->clinica->nome }}</b>
            </div>
            <div class="col-md-4 form-group">
                <label for="">Data Pagamento:</label><br>
                <b>{{ dataDbForm($financeiro->dt_pagamento) }}</b>
            </div>
        </div>
        <div class="row mt-2 gy-4 align-items-end mb-3">
            <div class="col-md-3 form-group">
                <label for="">Valor Consulta:</label><br>
                <b>R$ {{ valorDbForm($financeiro->vl_consulta) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Procedimentos:</label><br>
                <b>R$ {{ valorDbForm($financeiro->vl_procedimentos) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Desconto:</label><br>
                <b>R$ {{ valorDbForm($financeiro->vl_desconto) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Pagamento:</label><br>
                <b>R$ {{ valorDbForm($financeiro->vl_pagamento) }}</b>
            </div>
        </div>
        <div class="table-responsive mt-3">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Forma Pagamento</th>
                        <th>Parcelas</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $i=0;
                    @endphp
                    @foreach($financeiro->formas as $forma)
                        <tr>
                            <td>{{ ++$i }}</td>
                            <td>{{ $forma->forma_pagamento }}</td>
                            <td>{{ $forma->parcelas }}</td>
                            <td>R$ {{ valorDbForm($forma->vl_pagamento) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@if($financeiro->procedimentos()->count() > 0)
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h4 class="card-title">Procedimentos</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>Dt Cad</th>
                            <th>Paciente</th>
                            <th>Procedimento</th>
                            <th>Numero</th>
                            <th>Médico</th>
                            <th>Dt Aplicação</th>
                            <th>Valor</th>
                            <th>Situação Pg</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    @foreach($financeiro->procedimentos() as $proc)
                        @php
                        if($proc->situacao == "Agendado"){
                            $situacao = '<span class="badge rounded-pill bg-label-warning">Agendado</span>';
                        }
                        elseif($proc->situacao == "Fila de Aplicação"){
                            $situacao = '<span class="badge rounded-pill bg-label-primary">Fila de Aplicação</span>';
                        }
                        elseif($proc->situacao == "Atendimento"){
                            $situacao = '<span class="badge rounded-pill bg-label-danger">Fila de Aplicação</span>';
                        }
                        elseif($proc->situacao == "Aplicado"){
                            $situacao = '<span class="badge rounded-pill bg-label-success">Aplicado</span>';
                        }

                        if($proc->st_pagamento == 'Sim'){
                            $st_pagamento = "<span class='badge bg-success'>$proc->st_pagamento</span>";
                        }
                        else{
                            $st_pagamento = "<span class='badge bg-danger'>$proc->st_pagamento</span>";
                        }

                        @endphp
                        <tr>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu" data-popper-placement="bottom-end">
                                        <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.acessar', $proc->id) }}"><i class="mdi mdi-eye me-1"></i> Acessar</a>
                                    </div>
                                </div>
                            </td>
                            <td> <span style='display: none'>{{ strtotime($proc->data_cad) }}</span> {{ dataDbForm($proc->data_cad) }}</td>
                            <td>{{ $proc->paciente->nm_paciente }}</td>
                            <td>{{ $proc->codigo }}</td>
                            <td>{{ $proc->nr_procedimento }}</td>
                            <td>{{ $proc->medico }}</td>
                            <td>{{ dataDbForm($proc->data_aplicacao) }}</td>
                            <td>{{ valorDbForm($proc->valor) }}</td>
                            <td>{!! $st_pagamento !!}</td>
                            <td>{!! $situacao !!}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
