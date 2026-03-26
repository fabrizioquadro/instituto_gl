@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Imprimir Prontuário</h4>
            @if($link_zip)
                <a href="{{ $link_zip }}" download class="btn btn-primary">Baixar Todos</a>
            @endif
        </div>
        <hr>
        <ul>
            @foreach($array_arquivos as $arquivo)
            <li> <a class="btn btn-text-primary waves-effect waves-light" href="/public/procedimentos/{{ $arquivo['procedimento_id'] }}/relatorios/{{ $arquivo['arquivo'] }}" download>Semana {{ $arquivo['semana'] }}</a> </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
