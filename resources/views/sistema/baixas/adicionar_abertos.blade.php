@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Baixa Abertos</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.baixas.insert_abertos') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="estoque_aberto_id" name='estoque_aberto_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($abertos as $aberto)
                                <option value="{{ $aberto->id }}">{{ $aberto->codigo_barras }}</option>
                            @endforeach
                        </select>
                        <label for="estoque_aberto_id">Medicamento:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="quantidade" name="quantidade"/>
                        <label for="quantidade">Quantidade(mg):</label>
                    </div>
                </div>
            </div>
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline mb-4">
                        <textarea class="form-control h-px-100" id="motivo" name='motivo' required></textarea>
                        <label for="motivo">Motivo:</label>
                    </div>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
