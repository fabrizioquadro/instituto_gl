@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

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
            <h4 class="card-title">Adicionar Biopedância/Coleta</h4>
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
        <form action="{{ route('sistema.dashboard.insert_biopedancia_coleta') }}" method="post">
            @csrf
            <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="exames" name='exames' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Biopedância">Biopedância</option>
                            <option value="Coleta">Coleta</option>
                            <option value="Biopedância e Coleta">Biopedância e Coleta</option>
                        </select>
                        <label for="exames">Exame::</label>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Enviar Para Exame</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
