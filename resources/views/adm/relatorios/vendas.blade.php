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
            <h4 class="card-title">Relatório Vendas</h4>
        </div>
        <hr>
        <form action="{{ route('adm.relatorios.vendas.gerar') }}" method="post">
            @csrf
            <div class="row mt-2 gy-4 align-items-end mb-3">
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline">
                        <select id="paciente_id" name='paciente_id' class="select2 form-select">
                            <option value="">Opções</option>
                        </select>
                        <label for="paciente_id">Paciente:</label>
                    </div>
                </div>
            </div>
            <div class="row mt-2 gy-4">
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select id="clinica_id" name='clinica_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($clinicas as $clinica)
                                <option value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
                            @endforeach
                        </select>
                        <label for="clinica_id">Clinica:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select id="medicamento_id" name='medicamento_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($medicamentos as $medicamento)
                                <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                            @endforeach
                        </select>
                        <label for="medicamento_id">Med/Proc:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select id="medico" name='medico' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($medicos as $medico)
                                <option value="{{ $medico['profissional_id'] }}">{{ $medico['profissional_nome'] }}</option>
                            @endforeach
                        </select>
                        <label for="medicamento_id">Médico:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_inc" name="dt_inc"/>
                        <label for="dt_inc">Início:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_fn" name="dt_fn"/>
                        <label for="dt_fn">Final:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select id="situacao" name='situacao' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Aberta">Aberta</option>
                            <option value="Aplicada">Aplicada</option>
                        </select>
                        <label for="situacao">Situação:</label>
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
<script type="text/javascript">
window.addEventListener('load',()=>{
    $('.combobox').combobox();

    $('#paciente_id').select2({
        placeholder: "Escolha o Paciente.",
        allowClear: true,
        minimumInputLength: 2,
        ajax:{
            url:"{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
            dataType: "json",
            type: 'GET',
            delay: 250,
            data:function(params){
                return {
                    q: params.term,
                };
            },
            processResults: function(data){
                return {
                    results:data
                };
            },
        cache: true
        }
    });
});
</script>
@endsection
