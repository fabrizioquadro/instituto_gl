@php
$template = "layout.".session()->get('layout');
$paciente_id = request('paciente_id', '');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Procedimento</h4>
            <div>
                <a href="{{ route('sistema.procedimentos.imprimir_cadastro', $procedimento->codigo) }}" target="_blank" class="btn btn-label-info waves-effect me-2">
                    <span class="tf-icons mdi mdi-folder-open me-1"></span>
                    Visualizar Prontuário Completo
                </a>
                <a href="{{ route('sistema.medico.conjunto', [$procedimento->codigo, 'paciente_id' => $paciente_id]) }}" class="btn btn-outline-dark waves-effect">
                    <i class="mdi mdi-arrow-left me-1"></i> Voltar
                </a>
            </div>
        </div>
        <hr>
        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
            <span class="alert-icon mdi mdi-information-outline me-2"></span>
            <div>
                <strong>Modo de Visualização:</strong> você está apenas visualizando as informações deste procedimento.
            </div>
        </div>
        <div class="row gy-3">
            <div class="col-md-4">
                <label>Paciente:</label><br>
                <strong>{{ $procedimento->paciente ? $procedimento->paciente->nm_paciente : '-' }}</strong>
            </div>
            <div class="col-md-4">
                <label>Nascimento:</label><br>
                <strong>{{ ($procedimento->paciente && $procedimento->paciente->dt_nascimento) ? dataDbForm($procedimento->paciente->dt_nascimento) : '-' }}</strong>
            </div>
            <div class="col-md-4">
                <label>CPF:</label><br>
                <strong>{{ $procedimento->paciente ? $procedimento->paciente->cpf : '-' }}</strong>
            </div>
            <div class="col-md-4">
                <label>Médico:</label><br>
                <strong>{{ $procedimento->medico }}</strong>
            </div>
            <div class="col-md-4">
                <label>Código:</label><br>
                <strong>{{ $procedimento->codigo }}</strong>
            </div>
            <div class="col-md-4">
                <label>Nº Procedimento:</label><br>
                <strong>{{ $procedimento->nr_procedimento }} de {{ $procedimentos_conjunto->count() }}</strong>
            </div>
            <div class="col-md-4">
                <label>Data de Aplicação:</label><br>
                <strong>{{ dataDbForm($procedimento->data_aplicacao) }}</strong>
            </div>
            <div class="col-md-4">
                <label>Situação:</label><br>
                <strong>{{ $procedimento->situacao }}</strong>
            </div>
            <div class="col-md-4">
                <label>Pagamento:</label><br>
                <strong>{{ $procedimento->st_pagamento == 'Sim' ? 'Pago' : 'Não Pago' }}</strong>
                @if($procedimento->st_pagamento == 'Sim' && $procedimento->vl_pago)
                    ({{ valorDbForm($procedimento->vl_pago) }})
                @endif
            </div>
            <div class="col-md-4">
                <label>Valor:</label><br>
                <strong>{{ valorDbForm($procedimento->valor) }}</strong>
            </div>
            <div class="col-md-4">
                <label>Chegada / Atendimento / Finalização:</label><br>
                <strong>
                    {{ $procedimento->dt_hr_chegada ? date('d/m/Y H:i', strtotime($procedimento->dt_hr_chegada)) : '-' }}
                    /
                    {{ $procedimento->dt_hr_atendimento ? date('d/m/Y H:i', strtotime($procedimento->dt_hr_atendimento)) : '-' }}
                    /
                    {{ $procedimento->dt_hr_finalizacao ? date('d/m/Y H:i', strtotime($procedimento->dt_hr_finalizacao)) : '-' }}
                </strong>
            </div>
            <div class="col-md-12">
                <label>Observação Prévia:</label><br>
                <strong>{{ $procedimento->obs ?: 'Sem observações' }}</strong>
            </div>
        </div>
    </div>
</div>

@if($aplicacoes->count() > 0)
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <h5 class="card-title">Aplicações</h5>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Medicamento</th>
                            <th>Unidade</th>
                            <th>Quantidade</th>
                            <th>Situação</th>
                            <th>Lote</th>
                            <th>Enfermagem</th>
                            <th>Obs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($aplicacoes as $aplicacao)
                            <tr>
                                <td>{{ $aplicacao->medicamento ? $aplicacao->medicamento->nome : '-' }}</td>
                                <td>{{ $aplicacao->medicamento ? $aplicacao->medicamento->unidade : '-' }}</td>
                                <td>{{ $aplicacao->quantidade }}</td>
                                <td>{{ $aplicacao->situacao }}</td>
                                <td>{!! $aplicacao->lotes() !!}</td>
                                <td>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '-' }}</td>
                                <td>{{ $aplicacao->obs }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if($procedimento->st_biopedancia == 'Sim')
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <h5 class="card-title">Biopedância</h5>
            <hr>
            <p class="mb-0">{{ $procedimento->obs_biopedancia ?: 'Sem observações' }}</p>
        </div>
    </div>
@endif

@if($procedimento->st_coleta == 'Sim')
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <h5 class="card-title">Coleta</h5>
            <hr>
            <div class="row gy-3">
                <div class="col-md-4">
                    <label>Tipo de Coleta:</label><br>
                    <strong>{{ $procedimento->tp_coleta ?: '-' }}</strong>
                </div>
                <div class="col-md-8">
                    <label>Observação:</label><br>
                    <strong>{{ $procedimento->obs_coleta ?: 'Sem observações' }}</strong>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h5 class="card-title">Anexos do Conjunto</h5>
        <hr>
        <div class="list-group">
            @if($anexos->count() == 0)
                <div class="list-group-item" style="cursor: default !important">
                    Nenhum anexo para este conjunto de procedimentos.
                </div>
            @endif
            @foreach($anexos as $anexo)
                <a target="_blank" href="/public/procedimentos/{{ $anexo->procedimento_id }}/anexos/{{ $anexo->anexo }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                    <span><i class="mdi mdi-paperclip me-2"></i>{{ $anexo->nm_anexo }}</span>
                    <span class="badge bg-label-primary"><i class="mdi mdi-eye"></i> Visualizar</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

@if($observacoes->count() > 0)
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <h5 class="card-title">Observações Avulsas</h5>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%">Data / Hora</th>
                            <th style="width: 20%">Usuário</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($observacoes as $obs)
                            <tr>
                                <td>{{ $obs->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $obs->user ? $obs->user->nome : 'Sistema' }}</td>
                                <td style="white-space: pre-wrap;">{{ $obs->observacao }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
