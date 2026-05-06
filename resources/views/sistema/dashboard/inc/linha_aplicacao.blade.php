<tr>
    <td>
        @if($aplicacao->situacao == "Aberta" || $aplicacao->situacao == 'Pendente')
            <input {{ isset($visualizar) ? 'disabled' : '' }} class="form-check-input" data-medicamento="{{ $aplicacao->medicamento->unidade }}" type="checkbox" value="Sim" onclick="controle_pendente({{ $aplicacao->medicamento->id }}, this)" name="controle_pendente_{{ $aplicacao->medicamento->id }}" id="controle_pendente_{{ $aplicacao->medicamento->id }}"></td>
        @endif
    <td>{{ $aplicacao->medicamento->nome }} {!! $aplicacao->is_soro ? '<span class="text-primary font-weight-bold">(Soro)</span>' : '' !!}</td>
    <td>{{ $aplicacao->medicamento->unidade }}</td>
    <td>{{ $aplicacao->quantidade }}</td>
    @if($aplicacao->situacao == "Aberta" || $aplicacao->situacao == 'Pendente')
        @if($aplicacao->medicamento->unidade == "Ampola")
            <td><input {{ isset($visualizar) ? 'readonly' : 'required' }} onblur="busca_lote_por_codigo(this,{{ $aplicacao->medicamento->id }}, {{ $user->clinica_id }}, {{ $aplicacao->quantidade }})" type="text" name="codigo_barras_{{ $aplicacao->medicamento->id }}" id="codigo_barras_{{ $aplicacao->medicamento->id }}" class="form-control"></td>
            <td><input required readonly type="text" class="form-control" name="lote_{{ $aplicacao->medicamento->id }}" id="lote_{{ $aplicacao->medicamento->id }}"></td>
            <td></td>
        @elseif($aplicacao->medicamento->unidade == "Miligrama")
            <td id="td_aplicacao_codigo_{{ $aplicacao->medicamento->id }}"><input {{ isset($visualizar) ? 'readonly' : 'required' }} onblur="busca_lote_por_codigo_frasco(this,{{ $aplicacao->medicamento->id }}, {{ $user->clinica_id }}, {{ $aplicacao->quantidade }})" type="text" name="codigo_barras_{{ $aplicacao->medicamento->id }}" id="codigo_barras_{{ $aplicacao->medicamento->id }}" class="form-control"></td>
            <td id="td_aplicacao_lote_{{ $aplicacao->medicamento->id }}"><input required readonly type="text" class="form-control" name="lote_{{ $aplicacao->medicamento->id }}" id="lote_{{ $aplicacao->medicamento->id }}"></td>
            <td>
                @empty($visualizar)
                    <button title="Aplicação com 2 codigo" onclick="abre_modal_2_codigo({{ $aplicacao->medicamento->id }})" type="button" class="btn rounded-pill btn-icon btn-outline-secondary waves-effect">
                        <span class="tf-icons mdi mdi-numeric-2-box"></span>
                    </button>
                @endempty
            </td>
        @elseif($aplicacao->medicamento->unidade == "Procedimento")
            <td colspan='3'>
                <input type="text" name="codigo_barras_{{ $aplicacao->medicamento->id }}" id="codigo_barras_{{ $aplicacao->medicamento->id }}" class="form-control">
            </td>
        @endif
    @else
        <td>{!!$aplicacao->lotes()!!}</td>
        <td>{!!$aplicacao->codigos()!!}</td>
        <td></td>
    @endif
</tr>
