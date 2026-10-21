@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<style media="screen">
    .select2-selection__rendered{
        line-height: 40px !important;
    }
    .select2-selection{
        height: 40px !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Dashboard do Médico</h4>
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
        <p class="mb-3">Olá, <strong>{{ $user->nome }}</strong>. Digite o nome do paciente para visualizar os conjuntos de procedimentos.</p>
        <hr>
        <form action="{{ route('sistema.medico.dashboard') }}" method="get" id="form_pesquisa_medico">
            <div class="row gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select id="paciente_id" name="paciente_id" class="select2 form-select">
                            <option value="">Opções</option>
                            @if($paciente_selecionado)
                                <option value="{{ $paciente_selecionado->id }}" selected>{{ $paciente_selecionado->nm_paciente }} - {{ dataDbForm($paciente_selecionado->dt_nascimento) }}</option>
                            @endif
                        </select>
                        <label for="paciente_id">Paciente:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-start">
                        <button type="submit" class="btn btn-primary waves-effect">
                            <span class="tf-icons mdi mdi-magnify me-1"></span>
                            Pesquisar
                        </button>
                        @if($paciente_selecionado || $termo !== '')
                            <a href="{{ route('sistema.medico.dashboard') }}" class="btn btn-outline-dark waves-effect" style="margin-left: 10px">Limpar</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if($paciente_selecionado || ($termo !== '' && mb_strlen($termo) >= 2))
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Conjuntos de Procedimentos</h5>
                @if($pacientes->count() > 0)
                    <small class="text-muted">{{ $pacientes->count() }} paciente(s) encontrado(s)</small>
                @endif
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Paciente</th>
                            <th>Código</th>
                            <th>Médico</th>
                            <th>Dt Cadastro</th>
                            <th>Procedimentos</th>
                            <th>Situação</th>
                            <th>Pagamento</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$conjuntos || $conjuntos->count() == 0)
                            <tr>
                                <td colspan="8" class="text-center">Nenhum conjunto de procedimentos encontrado.</td>
                            </tr>
                        @else
                            @foreach($conjuntos as $conjunto)
                                @php
                                    $st_procedimento = $conjunto->get_st_procedimento();
                                    if($st_procedimento == "Aberto"){
                                        $situacao = '<span class="badge rounded-pill bg-label-warning">Aberto</span>';
                                    }
                                    elseif($st_procedimento == "Finalizado"){
                                        $situacao = '<span class="badge rounded-pill bg-label-success">Finalizado</span>';
                                    }
                                    else{
                                        $situacao = '<span class="badge rounded-pill bg-label-danger">'.$st_procedimento.'</span>';
                                    }

                                    $st_pagamento = $conjunto->get_st_pagamento();
                                    if($st_pagamento == 'Aberto'){
                                        $st_pagamento = "<span class='badge bg-danger'>$st_pagamento</span>";
                                    }
                                    elseif($st_pagamento == 'Total'){
                                        $st_pagamento = "<span class='badge bg-success'>$st_pagamento</span>";
                                    }
                                    else{
                                        $st_pagamento = "<span class='badge bg-warning'>$st_pagamento</span>";
                                    }
                                @endphp
                                <tr style="cursor: pointer" onclick="window.location.href='{{ route('sistema.medico.conjunto', [$conjunto->codigo, 'paciente_id' => $conjunto->paciente_id]) }}'">
                                    <td>{{ $conjunto->paciente ? $conjunto->paciente->nm_paciente : '-' }}</td>
                                    <td><small>{{ $conjunto->codigo }}</small></td>
                                    <td>{{ $conjunto->medico }}</td>
                                    <td>{{ dataDbForm($conjunto->data_cad) }}</td>
                                    <td>{{ $conjunto->get_nr_semanas() }}</td>
                                    <td>{!! $situacao !!}</td>
                                    <td>{!! $st_pagamento !!}</td>
                                    <td>
                                        <a href="{{ route('sistema.medico.conjunto', [$conjunto->codigo, 'paciente_id' => $conjunto->paciente_id]) }}" class="btn btn-sm btn-label-primary waves-effect">
                                            <i class="mdi mdi-eye me-1"></i> Acessar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            @if($conjuntos && $conjuntos->hasPages())
                <div class="mt-3">
                    {{ $conjuntos->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
@endif
<script>
window.addEventListener('load', function(){
    var form = document.getElementById('form_pesquisa_medico');

    $('#paciente_id').select2({
        placeholder: "Digite o nome do paciente",
        allowClear: true,
        minimumInputLength: 2,
        language: {
            inputTooShort: function(){ return 'Digite pelo menos 2 caracteres'; },
            noResults: function(){ return 'Nenhum paciente encontrado'; },
            searching: function(){ return 'Pesquisando...'; }
        },
        ajax: {
            url: "{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
            dataType: 'json',
            type: 'GET',
            delay: 250,
            data: function(params){
                return { q: params.term };
            },
            processResults: function(data){
                return { results: data };
            },
            cache: true
        }
    });

    $('#paciente_id').on('select2:select', function(){
        form.submit();
    });

    $('#paciente_id').on('select2:clear', function(){
        form.submit();
    });

    form.addEventListener('submit', function(e){
        if(!document.getElementById('paciente_id').value){
            e.preventDefault();
            $('#paciente_id').select2('open');
        }
    });
});
</script>
@endsection
