@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Medicamento</h4>
        </div>
        <hr>
        <form action="{{ route('adm.medicamentos.insert') }}" method="post">
            @csrf
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="nome" name="nome"/>
                        <label for="nome">Nome:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="fabricante" name="fabricante"/>
                        <label for="fabricante">Fabricante:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="unidade" name='unidade' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Ampola">Ampola</option>
                            <option value="Miligrama">Miligrama</option>
                            <option value="Procedimento">Procedimento</option>
                        </select>
                        <label for="unidade">Unidade:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="number" id="vasilhame" name="vasilhame"/>
                        <label for="vasilhame">Tamanho Ampola(mg):</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="ultimo_valor_pg" name="ultimo_valor_pg" onkeypress="return(MascaraMoeda(this,'.',',',event))"/>
                        <label for="ultimo_valor_pg">Último Valor Pago:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="vl_venda" name="vl_venda" onkeypress="return(MascaraMoeda(this,'.',',',event))"/>
                        <label for="vl_venda">Valor Venda (Ampola, miligrama ou procedimento):</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="estoque_minimo" name="estoque_minimo" value="0"/>
                        <label for="estoque_minimo">Estoque Minimo(*em miligrama):</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="aplicacao" name='aplicacao' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Sim">Sim</option>
                            <option value="Não">Não</option>
                        </select>
                        <label for="aplicacao">Gera Aplicação:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="situacao" name='situacao' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Ativo">Ativo</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                        <label for="situacao">Situação:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="number" id="aplicacao_feegow_id" name="aplicacao_feegow_id"/>
                        <label for="aplicacao_feegow_id">Feegow Aplicação Id:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="grupo_id" name='grupo_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($grupos as $grupo)
                                <option value="{{ $grupo->id }}">{{ $grupo->nome }}</option>
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
