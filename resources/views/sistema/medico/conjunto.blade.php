@php
$template = "layout.".session()->get('layout');
$paciente_id = request('paciente_id', '');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Conjunto de Procedimentos</h4>
            <div>
                <a href="{{ route('sistema.procedimentos.imprimir_cadastro', $procedimento->codigo) }}" target="_blank" class="btn btn-label-info waves-effect me-2">
                    <span class="tf-icons mdi mdi-folder-open me-1"></span>
                    Visualizar Prontuário Completo
                </a>
                <a href="{{ route('sistema.medico.dashboard', ['paciente_id' => $paciente_id]) }}" class="btn btn-outline-dark waves-effect">
                    <i class="mdi mdi-arrow-left me-1"></i> Voltar
                </a>
            </div>
        </div>
        <hr>
        <div class="row gy-3">
            <div class="col-md-4">
                <label>Paciente:</label><br>
                <strong>{{ $procedimento->paciente ? $procedimento->paciente->nm_paciente : '-' }}</strong>
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
                <label>Data de Cadastro:</label><br>
                <strong>{{ dataDbForm($procedimento->data_cad) }}</strong>
            </div>
            <div class="col-md-4">
                <label>Procedimentos no Conjunto:</label><br>
                <strong>{{ $procedimentos->count() }}</strong>
            </div>
            <div class="col-md-4">
                <label>Situação:</label><br>
                @php
                    $st_conjunto = $procedimento->get_st_procedimento();
                    $badge_conjunto = $st_conjunto == 'Finalizado' ? 'bg-label-success' : ($st_conjunto == 'Aberto' ? 'bg-label-warning' : 'bg-label-danger');
                @endphp
                <span class="badge rounded-pill {{ $badge_conjunto }}">{{ $st_conjunto }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h5 class="card-title">Procedimentos</h5>
        <hr>
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Nº</th>
                        <th>Dt Aplicação</th>
                        <th>Situação</th>
                        <th>Pagamento</th>
                        <th>Valor</th>
                        <th>Observação</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($procedimentos as $proc)
                        @php
                            if($proc->situacao == "Agendado"){
                                $situacao = '<span class="badge rounded-pill bg-label-warning">Agendado</span>';
                            }
                            elseif($proc->situacao == "Fila de Aplicação"){
                                $situacao = '<span class="badge rounded-pill bg-label-primary">Fila de Aplicação</span>';
                            }
                            elseif($proc->situacao == "Atendimento"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Atendimento</span>';
                            }
                            elseif($proc->situacao == "Aplicado"){
                                $situacao = '<span class="badge rounded-pill bg-label-success">Aplicado</span>';
                            }
                            elseif($proc->situacao == "Cancelado"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Cancelado</span>';
                            }
                            else{
                                $situacao = '<span class="badge rounded-pill bg-label-secondary">'.$proc->situacao.'</span>';
                            }

                            if($proc->st_pagamento == 'Sim'){
                                $st_pagamento = "<span class='badge bg-success'>Pago</span>";
                            }
                            else{
                                $st_pagamento = "<span class='badge bg-danger'>".$proc->st_pagamento."</span>";
                            }
                        @endphp
                        <tr style="cursor: pointer" onclick="window.location.href='{{ route('sistema.medico.procedimento', [$proc->id, 'paciente_id' => $paciente_id]) }}'">
                            <td>{{ $proc->nr_procedimento }}</td>
                            <td>{{ dataDbForm($proc->data_aplicacao) }}</td>
                            <td>{!! $situacao !!}</td>
                            <td>{!! $st_pagamento !!}</td>
                            <td>{{ valorDbForm($proc->valor) }}</td>
                            <td>{{ $proc->obs }}</td>
                            <td>
                                <a href="{{ route('sistema.medico.procedimento', [$proc->id, 'paciente_id' => $paciente_id]) }}" class="btn btn-sm btn-label-primary waves-effect">
                                    <i class="mdi mdi-eye me-1"></i> Visualizar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
