@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Combo</h4>
        </div>
        <hr>
        <form action="{{ route('adm.combos.update') }}" method="post">
            @csrf
            <input type="hidden" name="combo_id" value='{{ $combo->id }}'>
            <input type="hidden" name="contador" id="contador" value='1'>
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="nome" name="nome" value="{{ $combo->nome }}"/>
                        <label for="nome">Nome:</label>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <h5 class="card-title mt-1">Medicamentos</h5>
                <button type="button" class="btn btn-sm btn-primary" id="btn_adicionar_medicamento">Adicionar Medicamento</button>
            </div>
            <table class="table mt-3">
                <thead class="table-light">
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Valor Unitário</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id='tabelas_medicamentos'>
                    @foreach($combo->medicamentos as $med)
                        <tr id="linha_cad_{{ $med->id }}">
                            <td>{{ $med->medicamento->nome }}</td>
                            <td>{{ $med->quantidade }}</td>
                            <td>{{ valorDbForm($med->valor_unitario) }}</td>
                            <td>
                                <button onclick="excluir_cad({{ $med->id }})" type="button" class="btn btn-icon btn-outline-danger waves-effect">
                                    <span class="tf-icons mdi mdi-delete"></span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    <tr id='linha_med_1'>
                        <td>
                            <select required name="medicamento_id_1" class="form-control">
                                <option value=""></option>
                                @foreach($medicamentos as $medicamento)
                                    <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td> <input type="text" name="quantidade_1" class="form-control" required> </td>
                        <td> <input type="text" name="valor_1" class="form-control" required onkeypress="return(MascaraMoeda(this,'.',',',event))"> </td>
                        <td>
                            <button onclick="excluir_linha(1)" type="button" class="btn btn-icon btn-outline-danger waves-effect">
                                <span class="tf-icons mdi mdi-delete"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('btn_adicionar_medicamento').addEventListener('click', ()=>{
    contador = parseInt(document.getElementById('contador').value);
    contador++;
    document.getElementById('contador').value = contador;

    tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_med_' + contador);

    tr.innerHTML = `
    <td>
        <select required name="medicamento_id_${contador}" class="form-control">
            <option value=""></option>
            @foreach($medicamentos as $medicamento)
                <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
            @endforeach
        </select>
    </td>
    <td> <input type="text" name="quantidade_${contador}" class="form-control" required> </td>
    <td> <input type="text" name="valor_${contador}" class="form-control" required onkeypress="return(MascaraMoeda(this,'.',',',event))"> </td>
    <td>
        <button onclick="excluir_linha(${contador})" type="button" class="btn btn-icon btn-outline-danger waves-effect">
            <span class="tf-icons mdi mdi-delete"></span>
        </button>
    </td>
    `;

    document.getElementById('tabelas_medicamentos').appendChild(tr);
})

function excluir_linha(linha){
    if(confirm('Tem certeza que deseja excluir a linha selecionada?')){
        document.getElementById('linha_med_' + linha).remove();
    }
}

function excluir_cad(combo_medicamento_id){
    if(confirm('Tem certeza que deseja excluir a linha selecionada?')){
        $.getJSON(
            "{{ route('adm.combos.delete_medicamento') }}",
            {
                combo_medicamento_id : combo_medicamento_id
            },
            function(json){
                if(json.controle == 'true'){
                    document.getElementById('linha_cad_' + combo_medicamento_id).remove();
                }
                else{
                    alert('Aconteceu uma ação imprevista, atualize a pagina e tente novamente, persistindo o erro contate o administrador!');
                }
            }
        );
    }
}
</script>
@endsection
