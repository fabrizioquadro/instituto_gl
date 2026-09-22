{{-- Filtro de paciente (select2 com busca ajax) usado nos formulários do Relatórios 2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style media="screen">
    .select2-selection__rendered {
        line-height: 40px !important;
        border-color: red !important;
    }
    .select2-selection {
        height: 40px !important;
    }
</style>
<script type="text/javascript">
window.addEventListener('load', () => {
    $('.combobox').combobox();

    $('#paciente_id').select2({
        placeholder: "Escolha o Paciente.",
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: "{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
            dataType: "json",
            type: 'GET',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });
});
</script>
