@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Fornecedor</h4>
        </div>
        <hr>
        <form action="{{ route('adm.fornecedores.insert') }}" method="post">
            @csrf
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="nome" name="nome"/>
                        <label for="nome">Nome:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="cnpj" name="cnpj" maxlength="18" onkeypress="formatar('##.###.###/####-##', this)"/>
                        <label for="cnpj">CNPJ:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="email" id="email" name="email"/>
                        <label for="email">Email:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="tel" name="tel" maxlength="15" onkeypress="mascara( this, mtel )"/>
                        <label for="tel">Telefone:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="cel" name="cel" maxlength="15" onkeypress="mascara( this, mtel )"/>
                        <label for="cel">Celular:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select required id="situacao" name='situacao' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Ativa">Ativa</option>
                            <option value="Inativa">Inativa</option>
                        </select>
                        <label for="situacao">Situação:</label>
                    </div>
                </div>
                <div class="col-md-12 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
