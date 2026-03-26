@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-danger mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Excluir Combo</h4>
        </div>
        <hr>
        <form action="{{ route('adm.combos.delete') }}" method="post">
            @csrf
            <input type="hidden" name="combo_id" value='{{ $combo->id }}'>
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-12">
                    <p>Tem Certeza que deseja excluir o combo {{ $combo->nome }}?</p>
                </div>
            </div>
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-danger me-2">Excluir</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
