@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Medicamento</h4>
        </div>
        <hr>
        <form action="{{ route('adm.medicamentos.update') }}" method="post">
            @csrf
            <input type="hidden" name="medicamento_id" value="{{ $medicamento->id }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="nome" name="nome" value="{{ $medicamento->nome }}"/>
                        <label for="nome">Nome:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="fabricante" name="fabricante" value="{{ $medicamento->fabricante }}"/>
                        <label for="fabricante">Fabricante:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="unidade" name='unidade' class="select2 form-select">
                            <option value="">Opções</option>
                            <option @if($medicamento->unidade == "Ampola") selected @endif value="Ampola">Ampola</option>
                            <option @if($medicamento->unidade == "Miligrama") selected @endif value="Miligrama">Miligrama</option>
                            <option @if($medicamento->unidade == "Procedimento") selected @endif value="Procedimento">Procedimento</option>
                        </select>
                        <label for="unidade">Unidade:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input @if($medicamento->unidade == 'Miligrama') required value='{{ $medicamento->vasilhame }}' @else readonly @endif class="form-control" type="number" id="vasilhame" name="vasilhame"/>
                        <label for="vasilhame">Tamanho Ampola(mg):</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="ultimo_valor_pg" name="ultimo_valor_pg" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($medicamento->ultimo_valor_pg) }}"/>
                        <label for="ultimo_valor_pg">Último Valor Pago:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="vl_venda" name="vl_venda" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($medicamento->vl_venda) }}"/>
                        <label for="vl_venda">Valor Venda (Valor por ampola ou por miligrama):</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="estoque_minimo" name="estoque_minimo" value="{{ $medicamento->estoque_minimo }}"/>
                        <label for="estoque_minimo">Estoque Minimo(*em miligrama):</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="aplicacao" name='aplicacao' class="select2 form-select">
                            <option value="">Opções</option>
                            <option @if($medicamento->aplicacao == "Sim") selected @endif value="Sim">Sim</option>
                            <option @if($medicamento->aplicacao == "Não") selected @endif value="Não">Não</option>
                        </select>
                        <label for="aplicacao">Gera Aplicação:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="situacao" name='situacao' class="select2 form-select">
                            <option value="">Opções</option>
                            <option @if($medicamento->situacao == "Ativo") selected @endif value="Ativo">Ativo</option>
                            <option @if($medicamento->situacao == "Inativo") selected @endif value="Inativo">Inativo</option>
                        </select>
                        <label for="situacao">Situação:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="number" id="aplicacao_feegow_id" name="aplicacao_feegow_id" value="{{ $medicamento->aplicacao_feegow_id }}"/>
                        <label for="aplicacao_feegow_id">Feegow Aplicação Id:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="grupo_id" name='grupo_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($grupos as $grupo)
                                <option @if($grupo->id == $medicamento->grupo_id) selected @endif value="{{ $grupo->id }}">{{ $grupo->nome }}</option>
                            @endforeach
                        </select>
                        <label for="grupo_id">Grupo:</label>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
document.getElementById('unidade').addEventListener('change', (e)=>{
    if(e.target.value == "Miligrama"){
        document.getElementById('vasilhame').value = '';
        document.getElementById('vasilhame').removeAttribute('readonly');
        document.getElementById('vasilhame').setAttribute('required','required');
    }
    else{
        document.getElementById('vasilhame').value = '';
        document.getElementById('vasilhame').setAttribute('readonly','readonly');
        document.getElementById('vasilhame').removeAttribute('required');
    }
})
</script>
@endsection
