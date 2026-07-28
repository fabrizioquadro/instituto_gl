<tr>
    <th>{{ $aplicacao->medicamento->nome }} {!! $aplicacao->is_soro ? '<span class="text-primary font-weight-bold">(Soro)</span>' : '' !!}</th>
    <th>{{ $aplicacao->medicamento->unidade }}</th>
    <th>{{ $aplicacao->quantidade }}</th>
    <th>R$ {{ valorDbForm($aplicacao->valor) }}</th>
    <th>R$ {{ valorDbForm($aplicacao->total) }}</th>
    <th>{{ $aplicacao->obs }}</th>
    <th>{{ $aplicacao->situacao }}</th>
    <th>{{ $dt_aplicacao }}</th>
    <th>{!! $aplicacao->lotes() !!}</th>
    <th>{!! $aplicacao->codigos() !!}</th>
    <th>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '' }}</th>
</tr>
