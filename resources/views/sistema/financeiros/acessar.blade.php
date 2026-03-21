@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Acessar Financeiro</h4>
            <a href="{{ route('sistema.financeiros.adicionar_pagamento', $financeiro->id) }}" class="btn btn-sm btn-primary">Adicionar Pagamento</a>
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
            <div class="col-md-3 form-group">
                <label for="">Paciente:</label><br>
                <b>{{ $financeiro->paciente->nm_paciente }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Grupo:</label><br>
                <b>{{ $financeiro->procedimentos()->first() ? $financeiro->procedimentos()->first()->codigo : '' }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Clinica:</label><br>
                <b>{{ $financeiro->clinica->nome }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Data Cadastro:</label><br>
                <b>{{ dataDbForm($financeiro->dt_pagamento) }}</b>
            </div>
        </div>
        <div class="row mt-2 gy-4 align-items-end mb-3">
            <div class="col-md-2 form-group">
                <label for="">Valor Procedimentos:</label><br>
                <b>R$ {{ valorDbForm($financeiro->valor_procedimentos()) }}</b>
            </div>
            <div class="col-md-2 form-group">
                <label for="">Valor Aplicações:</label><br>
                <b>R$ {{ valorDbForm($financeiro->valor_aplicacaos()) }}</b>
            </div>
            <div class="col-md-2 form-group">
                <label for="">Valor Desconto:</label><br>
                <b>R$ {{ valorDbForm($financeiro->vl_desconto) }}</b>
            </div>
            <div class="col-md-2 form-group">
                <label for="">Valor Adicional:</label><br>
                <b>R$ {{ valorDbForm($financeiro->vl_adicional) }}</b>
            </div>
            <div class="col-md-2 form-group">
                <label for="">Valor Pagamento:</label><br>
                <b>R$ {{ valorDbForm($financeiro->formas()->sum('vl_pagamento')) }}</b>
            </div>
        </div>
        <div class="row mt-2 gy-4 align-items-end mb-3">
            <div class="col-md-3 form-group">
                <label for="">Valor Total:</label><br>
                <b>R$ {{ valorDbForm($financeiro->valor_procedimentos() + $financeiro->valor_aplicacaos() + $financeiro->vl_adicional - $financeiro->vl_desconto) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Restante:</label><br>
                <b>R$ {{ valorDbForm($financeiro->valor_procedimentos() + $financeiro->valor_aplicacaos() + $financeiro->vl_adicional - $financeiro->vl_desconto - $financeiro->formas()->sum('vl_pagamento')) }}</b>
            </div>
        </div>
        <div class="row mt-2 gy-4 align-items-end mb-3">
            <div class="col-md-3 form-group">
                <label for="">Observação:</label><br>
                <b>{{ $financeiro->obs_pagamento }}</b>
            </div>
        </div>
        <h5 class="card-title mt-5">Pagamentos</h5>
        <div class="table-responsive mt-3">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Forma Pagamento</th>
                        <th>Parcelas</th>
                        <th>Valor</th>
                        <th>Cadastro</th>
                        <th></th>
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
                            <td>{{ $forma->cadastrante ? $forma->cadastrante->nome : '' }}</td>
                            <td>
                                @if(session()->has('administrador'))
                                    <button title="Excluir Pagamento" onclick="excluir_pagamento({{ $forma->id }})" type="button" class="btn btn-icon btn-outline-danger waves-effect">
                                        <span class="tf-icons mdi mdi-delete"></span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"> <b>TOTAL</b> </td>
                        <td> <b>R$ {{ valorDbForm($financeiro->formas()->sum('vl_pagamento')) }}</b> </td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
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
                        else{
                            $situacao = '<span class="badge rounded-pill bg-label-warning">'.$proc->situacao.'</span>';
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
<script>
function excluir_pagamento(id){
    if(confirm('Tem certeza que deseja excluir este pagamento?')){
        window.location.href = "{{ route('sistema.financeiros.delete_pagamento') }}/" + id
    }
}
</script>
@endsection
