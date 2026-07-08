@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h5 class="card-title">Editar Pagamento</h5>
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
        <form id='formulario' action="{{ route('sistema.financeiros.update_pagamento') }}" method="post">
            @csrf
            <input type="hidden" name="id" value="{{ $forma->id }}">
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Forma Pagamento</th>
                                    <th>Parcelas</th>
                                    <th>ID Pagamento / DOC</th>
                                    <th>Data Lançamento</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select required id="forma_pagamento" onchange="controle_parcelas()" name='forma_pagamento' class="form-control">
                                            <option value="">Opções</option>
                                            <option {{ $forma->forma_pagamento == 'Dinheiro' ? 'selected' : '' }} value="Dinheiro">Dinheiro</option>
                                            <option {{ $forma->forma_pagamento == 'Débito' ? 'selected' : '' }} value="Débito">Débito</option>
                                            <option {{ $forma->forma_pagamento == 'Crédito' ? 'selected' : '' }} value="Crédito">Crédito</option>
                                            <option {{ $forma->forma_pagamento == 'Pix' ? 'selected' : '' }} value="Pix">Pix</option>
                                            <option {{ $forma->forma_pagamento == 'Link de Pagamento' ? 'selected' : '' }} value="Link de Pagamento">Link de Pagamento</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select {{ $forma->forma_pagamento == 'Crédito' ? '' : 'disabled' }} id="parcelas" name='parcelas' class="form-control">
                                            <option value="">Opções</option>
                                            @for($i=1; $i<=10; $i++)
                                                <option {{ $forma->parcelas == $i ? 'selected' : '' }} value="{{ $i }}">{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td><input class="form-control" type="text" id="id_pagamento" name="id_pagamento" value="{{ $forma->id_pagamento }}"/></td>
                                    <td><input required class="form-control" type="date" id="created_at" name="created_at" value="{{ date('Y-m-d', strtotime($forma->created_at)) }}"/></td>
                                    <td><input required class="form-control valor" type="text" id="vl_pagamento" name="vl_pagamento" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($forma->vl_pagamento) }}"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6 form-group">
                            <button type="submit" class="btn btn-primary me-2" id="botao_salvar">Salvar Alterações</button>
                            <a href="{{ route('sistema.financeiros.acessar', $financeiro->id) }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
function controle_parcelas(){
    if(document.getElementById('forma_pagamento').value == "Crédito"){
        document.getElementById('parcelas').removeAttribute('disabled');
        document.getElementById('parcelas').setAttribute('required','required');
    }
    else{
        document.getElementById('parcelas').setAttribute('disabled','disabled');
        document.getElementById('parcelas').removeAttribute('required');
    }
}
</script>
@endsection
