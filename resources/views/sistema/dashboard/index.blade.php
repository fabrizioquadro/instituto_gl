@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<style media="screen">
    .select2-selection__rendered{
        line-height: 40px !important;
        border-color: red !important;
    }
    .select2-selection{
        height: 40px !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Secretaria</h4>
            <a href="{{ route('sistema.procedimentos.adicionar','sistema_dashboard') }}" class="btn btn-label-secondary waves-effect">
                <span class="tf-icons mdi mdi-needle me-1"></span>
                Adicionar Procedimento
            </a>
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
        <form action="{{ route('sistema.dashboard') }}" method="post">
            @csrf
            <input type="hidden" name="paciente_controle" value="{{ $dados_pesquisa['paciente_controle'] }}">
            <div class="row gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="paciente_id" name='paciente_id' class="select2 form-select">
                            <option value="">Opções</option>
                        </select>
                        <label for="paciente_id">Escolha um Paciente:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-start">
                        <button type="submit" class="btn btn-label-primary waves-effect">
                            <span class="tf-icons mdi mdi-magnify me-1"></span>
                            Pesquisar
                        </button>
                        <a href="{{ route('sistema.dashboard') }}" style="margin-left: 10px" class="btn btn-outline-dark waves-effect">Limpar Filtros</a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between mt-5 mb-4">
                <h5 class="card-title">Procedimentos @if($paciente) {{ ": ".$paciente->nm_paciente  }} @else Gerais @endif</h5>
                @if($paciente_id)
                    <a href="{{ route('sistema.dashboard.add_biopedancia_coleta', $paciente_id) }}" class="btn btn-sm btn-outline-primary waves-effect">Adicionar Biopedância/Coleta</a>
                @endif
            </div>
            <h6 class="card-title">Filtros</h6>
            <div class="d-flex justify-content-between align-items-center row py-3 gap-3 gap-md-0">
                <div class="col-md-4 product_status">
                    <input onchange="submit()" type="date" id="dt_procedimentos" name="dt_procedimentos" class="form-control" value="{{ $dados_pesquisa['dt_procedimentos'] }}">
                </div>
                <div class="col-md-4 product_category">
                    <select onchange="submit()" id="st_pagamento" name="st_pagamento" class="form-select text-capitalize">
                        <option value="">Pagamento</option>
                        <option @if($dados_pesquisa['st_pagamento'] == 'Sim') selected @endif value="Sim">Pago</option>
                        <option @if($dados_pesquisa['st_pagamento'] == 'Não') selected @endif value="Não">Não Pago</option>
                    </select>
                </div>
                <div class="col-md-4 product_stock">
                    <select onchange="submit()" id="situacao" name="situacao" class="form-select text-capitalize">
                        <option value=""> Situação </option>
                        <option @if($dados_pesquisa['situacao'] == "Agendado") selected @endif  value="Agendado">Agendado</option>
                        <option @if($dados_pesquisa['situacao'] == "Atendimento") selected @endif  value="Atendimento">Atendimento</option>
                        <option @if($dados_pesquisa['situacao'] == "Fila de Aplicação") selected @endif  value="Fila de Aplicação">Fila de Aplicação</option>
                        <option @if($dados_pesquisa['situacao'] == "Pendente") selected @endif  value="Pendente">Pendente</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Aplicação</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Cad</th>
                        <th>Pagamento</th>
                        <th>Valor</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @if($procedimentos->count() == 0)
                        <tr>
                            <td colspan="7">Nenhum procedimento encontrado</td>
                        </tr>
                    @endif
                    @foreach($procedimentos as $procedimento)
                    @php
                    $st_procedimento = $procedimento->get_st_procedimento();
                    if($st_procedimento == "Aberto"){
                        $situacao = '<span class="badge rounded-pill bg-label-warning">'.$st_procedimento.'</span>';
                    }
                    elseif($st_procedimento == "Finalizado"){
                        $situacao = '<span class="badge rounded-pill bg-label-success">'.$st_procedimento.'</span>';
                    }

                    $st_pagamento = $procedimento->get_st_pagamento();
                    if($st_pagamento == 'Aberto'){
                        $st_pagamento = "<span class='badge bg-danger'>$st_pagamento</span>";
                    }
                    elseif($st_pagamento == 'Total'){
                        $st_pagamento = "<span class='badge bg-success'>$st_pagamento</span>";
                    }
                    elseif($st_pagamento == 'Parcial'){
                        $st_pagamento = "<span class='badge bg-warning'>$st_pagamento</span>";
                    }

                    @endphp
                        <tr style="cursor: pointer" onclick="acessa_procedimento({{ $procedimento->id }})">
                            <td>{{ dataDbForm($procedimento->data_aplicacao) }}</td>
                            <td>{{ $procedimento->paciente->nm_paciente }}</td>
                            <td>{{ $procedimento->medico }}</td>
                            <td>{{ dataDbForm($procedimento->data_cad) }}</td>
                            <td>{!! $st_pagamento !!}</td>
                            <td>{{ valorDbForm($procedimento->valor) }}</td>
                            <td>{!! $situacao !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card card-border-shadow-danger mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Procedimentos Atrasados</h4>
        </div>
        <hr>
        <h6 class="card-title">Filtros</h6>
        <div class="d-flex justify-content-between align-items-center row py-3 gap-3 gap-md-0">
            <div class="col-md-6">
                <select onchange='get_atrasados_filtro()' id="st_pagamento_atrasados" name="st_pagamento_atrasados" class="form-select text-capitalize">
                    <option value="">Pagamento</option>
                    <option value="Sim">Pago</option>
                    <option value="Não">Não Pago</option>
                </select>
            </div>
            <div class="col-md-6">
                <select onchange='get_atrasados_filtro()' id="situacao_atrasados" name="situacao_atrasados" class="form-select text-capitalize">
                    <option value=""> Situação </option>
                    <option value="Iniciado">Iniciado</option>
                    <option value="Não Iniciado">Não Iniciado</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Cad</th>
                        <th>Aplicação</th>
                        <th>Dias Atraso</th>
                        <th>Pagamento</th>
                        <th>Valor</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody id='tabela_atrasados'>
                    @if($proc_atrasados->count() == 0)
                        <tr>
                            <td colspan="8">Nenhum procedimento encontrado</td>
                        </tr>
                    @endif
                    @foreach($proc_atrasados as $procedimento)
                        @php
                        $data = date('Y-m-d');
                        $segundos = strtotime($data) - strtotime($procedimento->data_aplicacao);
                        $dias = $segundos / 86400;
                        @endphp
                        <tr style="cursor: pointer" onclick="acessa_procedimento({{ $procedimento->id }})">
                            <td>{{ $procedimento->paciente->nm_paciente }}</td>
                            <td>{{ $procedimento->medico }}</td>
                            <td>{{ dataDbForm($procedimento->data_cad) }}</td>
                            <td>{{ dataDbForm($procedimento->data_aplicacao) }}</td>
                            <td>{{ $dias }}</td>
                            <td>{{ $procedimento->st_pagamento }}</td>
                            <td>{{ valorDbForm($procedimento->valor) }}</td>
                            <td>{{ $procedimento->situacao }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">

function get_atrasados_filtro(){
    situacao = document.getElementById('situacao_atrasados').value;
    st_pagamento = document.getElementById('st_pagamento_atrasados').value;
    $.getJSON(
        '{{ route("sistema.dashboard.filtrar_atrasados") }}',
        {
            situacao : situacao,
            st_pagamento : st_pagamento
        },
        function(json){
            document.getElementById('tabela_atrasados').innerHTML = json.html;
        }
    );
}

function acessa_procedimento(procedimento_id){
    window.location.href = "/sistema/procedimentos/acessar/" + procedimento_id + "/sistema_dashboard";
}

window.addEventListener('load',()=>{
    $('.combobox').combobox();

    $('#paciente_id').select2({
        placeholder: "Escolha o Paciente.",
        allowClear: true,
        minimumInputLength: 2,
        ajax:{
            url:"{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
            dataType: "json",
            type: 'GET',
            delay: 250,
            data:function(params){
                return {
                    q: params.term,
                };
            },
            processResults: function(data){
                return {
                    results:data
                };
            },
        cache: true
        }
    });
});
</script>
@endsection
