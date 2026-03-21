@extends('layout.admin')

@section('conteudo')
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
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Estoques</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Medicamento</th>
                        <th>Unidade</th>
                        <th>Estoque Mínimo</th>
                        <th>Qt Total</th>
                        @foreach($clinicas as $clinica)
                            <th>{{ $clinica->nome }}</th>
                        @endforeach
                    </tr>
                </thead>
                @foreach($array_view as $linha)
                    @php
                    if($linha['estoque_minimo'] > $linha['quantidade']){
                        $color = "red";
                    }
                    else{
                        $color = '';
                    }
                    @endphp
                    <tr>
                        <td>
                            <button onclick="abre_estoque_lotes({{ $linha['id'] }})" type="button" class="btn btn-sm rounded-pill btn-icon btn-outline-primary waves-effect">
                                <span class="tf-icons mdi mdi-plus"></span>
                            </button>
                        </td>
                        <td style="color: {{ $color }}">{{ $linha['medicamento'] }}</td>
                        <td style="color: {{ $color }}">{{ $linha['unidade'] }}</td>
                        <td style="color: {{ $color }}">{{ $linha['estoque_minimo'] }}</td>
                        <td style="color: {{ $color }}">{{ $linha['quantidade'] }}</td>
                        @foreach($clinicas as $clinica)
                            <td style="color: {{ $color }}">{{ $linha[$clinica->nome] }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Estoques Abertos</h4>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Clinica</th>
                        <th>Medicamento</th>
                        <th>Abertura</th>
                        <th>Usuário</th>
                        <th>Lote</th>
                        <th>C. Barras</th>
                        <th>Frasco</th>
                        <th>Restante</th>
                    </tr>
                </thead>
                @foreach($array_abertos as $linha)
                    <tr>
                        <td>{{ $linha['clinica'] }}</td>
                        <td>{{ $linha['medicamento'] }}</td>
                        <td>{{ $linha['abertura'] }}</td>
                        <td>{{ $linha['usuario'] }}</td>
                        <td>{{ $linha['lote'] }}</td>
                        <td>{{ $linha['codigo_barras'] }}</td>
                        <td>{{ $linha['frasco'] }}</td>
                        <td>{{ $linha['restante'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_lotes" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_lotes_nome_medicamento"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Lote</th>
                                <th>C. Barras</th>
                                <th>Vencimento</th>
                                <th>Qt Total</th>
                                @foreach($clinicas as $clinica)
                                    <th>{{ $clinica->nome }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id='modal_lotes_tabela_lotes'>

                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<form target="_blank" id='formulario' action="{{ route('adm.estoques.exportar') }}" method="post">
    @csrf
    <input type="hidden" name="data" id="data">
</form>
<script>
document.getElementById('exportar').addEventListener('click', ()=>{
    document.getElementById('formulario').submit();
})

</script>

<script type="text/javascript">
var modalLotes;

function abre_estoque_lotes(medicamento_id){
    $.getJSON(
        "{{ route('adm.estoques.get_lotes_medicamento') }}",
        { medicamento_id : medicamento_id },
        function(json){
            document.getElementById('modal_lotes_nome_medicamento').innerHTML = json.medicamento_nome;
            document.getElementById('modal_lotes_tabela_lotes').innerHTML = json.html;
            modalLotes = new bootstrap.Modal(document.getElementById('modal_lotes'));
            modalLotes.show();
        }
    );
}
</script>

@endsection
