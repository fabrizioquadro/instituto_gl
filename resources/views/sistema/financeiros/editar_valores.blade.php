@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h5 class="card-title">Editar Financeiro (Desconto / Adicional / Observação)</h5>
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
        <form id='formulario' action="{{ route('sistema.financeiros.update_valores') }}" method="post">
            @csrf
            <input type="hidden" name="id" value="{{ $financeiro->id }}">
            <div class="row mt-3">
                <div class="col-md-6 form-group mb-3">
                    <label for="vl_desconto">Valor Desconto:</label>
                    <input required class="form-control" type="text" id="vl_desconto" name="vl_desconto" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($financeiro->vl_desconto) }}"/>
                </div>
                <div class="col-md-6 form-group mb-3">
                    <label for="vl_adicional">Valor Adicional:</label>
                    <input required class="form-control" type="text" id="vl_adicional" name="vl_adicional" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($financeiro->vl_adicional) }}"/>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 form-group mb-3">
                    <label for="obs_pagamento">Observação:</label>
                    <input class="form-control" type="text" id="obs_pagamento" name="obs_pagamento" value="{{ $financeiro->obs_pagamento }}"/>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2" id="botao_salvar">Salvar e Recalcular</button>
                    <a href="{{ route('sistema.financeiros.acessar', $financeiro->id) }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
