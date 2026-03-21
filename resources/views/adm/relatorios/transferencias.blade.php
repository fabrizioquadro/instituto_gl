@extends('layout.admin')

@section('conteudo')
<style media="screen">
    .select2-selection__rendered{
        line-height: 40px !important;
        border-color: red !important;
    }
    .select2-selection{
        height: 40px !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório Transferências</h4>
        </div>
        <hr>
        <form action="{{ route('adm.relatorios.transferencias.gerar') }}" method="post">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_inc" name="dt_inc"/>
                        <label for="dt_inc">Início:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_fn" name="dt_fn"/>
                        <label for="dt_fn">Final:</label>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Gerar</button>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection
