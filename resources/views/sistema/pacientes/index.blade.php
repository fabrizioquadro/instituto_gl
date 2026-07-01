@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Pacientes</h4>
            <a href="{{ route('sistema.pacientes.atualizar_integracao') }}" class="btn btn-primary">Atualizar Integração</a>
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
        <div class="table-responsive">
            <table class="tabela-index table" id="table-index">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Feegow Id</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
window.addEventListener('load',()=>{
  $('#table-index').DataTable({
    "processing": true,
    "serverSide": true,
    "ajax": "{{ route('sistema.pacientes.index_pesq') }}",
    order: [[0, 'asc']],
    "language": {
			"sEmptyTable": "Nenhum registro encontrado",
      "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
      "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
      "sInfoFiltered": "(Filtrados de _MAX_ registros)",
      "sInfoPostFix": "",
      "sInfoThousands": ".",
      "sLengthMenu": "_MENU_ resultados por página",
      "sLoadingRecords": "Carregando...",
      "sProcessing": "Processando...",
      "sZeroRecords": "Nenhum registro encontrado",
      "sSearch": "Pesquisar",
      "oPaginate": {
        "sNext": "Próximo",
        "sPrevious": "Anterior",
        "sFirst": "Primeiro",
        "sLast": "Último"
      },
      "oAria": {
        "sSortAscending": ": Ordenar colunas de forma ascendente",
        "sSortDescending": ": Ordenar colunas de forma descendente"
      }
    }
  });
});

var modalObservacoes;

function abrir_obs(paciente_id){
    $.getJSON(
        "{{ route('sistema.pacientes.get_paciente_ajax') }}",
        {
            paciente_id : paciente_id
        },
        function(json){
            document.getElementById('modal_observacoes_paciente_id').value = paciente_id;
            document.getElementById('modal_observacoes_titulo').innerText = 'Observações: ' + json.nm_paciente;
            let obs = json.obs ? json.obs : '';
            document.getElementById('modal_observacoes_texto').value = obs;
            
            modalObservacoes = new bootstrap.Modal(document.getElementById('modal_observacoes'));
            modalObservacoes.show();
        }
    );
}

function salvar_obs(){
    let paciente_id = document.getElementById('modal_observacoes_paciente_id').value;
    let obs = document.getElementById('modal_observacoes_texto').value;
    
    $.post(
        "{{ route('sistema.pacientes.salvar_obs_ajax') }}",
        {
            _token: "{{ csrf_token() }}",
            paciente_id: paciente_id,
            obs: obs
        },
        function(json){
            if(json.success){
                modalObservacoes.hide();
                alert(json.message);
            } else {
                alert('Erro: ' + json.message);
            }
        }
    ).fail(function(xhr) {
        alert('Erro ao salvar observações.');
    });
}
</script>

<!-- Modal Observações -->
<div class="modal fade" id="modal_observacoes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_observacoes_titulo">Observações do Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_observacoes_paciente_id">
                <div class="row">
                    <div class="col mb-3">
                        <textarea id="modal_observacoes_texto" class="form-control" rows="8" style="resize: none;"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="salvar_obs()">Salvar</button>
            </div>
        </div>
    </div>
</div>
@endsection
