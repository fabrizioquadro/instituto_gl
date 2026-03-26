@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Clinica</h4>
        </div>
        <hr>
        <form action="{{ route('adm.clinicas.insert') }}" method="post">
            @csrf
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="nome" name="nome"/>
                        <label for="nome">Nome:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="cnpj" name="cnpj" maxlength="18" onkeypress="formatar('##.###.###/####-##', this)"/>
                        <label for="cnpj">CNPJ:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="id_unidade_feegow" name='id_unidade_feegow' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($unidades as $unidade)
                                <option value="{{ $unidade['unidade_id'] }}">{{ $unidade['unidade_id']." - ".$unidade['nome']." ".$unidade['cnpj'] }}</option>
                            @endforeach
                        </select>
                        <label for="id_unidade_feegow">Unidade Flegow:</label>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
