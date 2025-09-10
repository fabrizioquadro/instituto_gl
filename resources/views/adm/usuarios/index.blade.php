@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Usuários da Clinica {{ $clinica->nome }}</h4>
            <a href="{{ route('adm.usuarios.adicionar') }}" class="btn btn-primary">Adicionar</a>
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
                        <th></th>
                        <th>Imagem</th>
                        <th>Nome</th>
                        <th>Clinica</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        @foreach($opcoes as $opt)
                            <th style="text-transform: capitalize">{{ str_replace('_',' ', $opt) }}</th>
                        @endforeach
                    </tr>
                </thead>
                @foreach($users as $user)
                    @php
                    if($user->imagem){
                        $avatar = "<img src='/public/img/usuarios/$user->imagem' style='height:40px; border-radius: 20px'>";
                    }
                    else{
                        $avatar = "
                        <div class='avatar me-2'>
                            <span class='avatar-initial rounded-circle bg-label-secondary'>".substr($user->nome,0,2)."</span>
                        </div>
                        ";
                    }
                    @endphp
                    <tr>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu" data-popper-placement="bottom-end">
                                    <a class="dropdown-item waves-effect" href="{{ route('adm.usuarios.editar', $user->id) }}"><i class="mdi mdi-pencil-outline me-1"></i> Editar</a>
                                    <a class="dropdown-item waves-effect" href="{{ route('adm.usuarios.excluir', $user->id) }}"><i class="mdi mdi-trash-can-outline me-1"></i> Excluir</a>
                                    <a class="dropdown-item waves-effect" href="{{ route('adm.usuarios.alterar_senha', $user->id) }}"><i class="mdi mdi-lock-reset me-1"></i> Alterar Senha</a>
                                </div>
                            </div>
                        </td>
                        <td>{!! $avatar !!}</td>
                        <td>{{ $user->nome }}</td>
                        <td>{{ $user->clinica->nome }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->tipo }}</td>
                        @foreach($opcoes as $opt)
                            <td>{{ $user->$opt }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
<script>
window.addEventListener('load',()=>{
  $('#table-index').DataTable({
    order: [[1, 'asc']],
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
})

</script>
@endsection
