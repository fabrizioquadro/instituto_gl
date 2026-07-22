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
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline mb-4">
                        <select class="form-select" id="motivo_baixa" name="motivo_baixa" required>
                            <option value="">Selecione um motivo</option>
                            <option value="Abastecimento Lidocaína">Abastecimento Lidocaína</option>
                            <option value="Abastecimento Mounjaro Núcleo">Abastecimento Mounjaro Núcleo</option>
                            <option value="Abastecimento Unidade Núcleo">Abastecimento Unidade Núcleo</option>
                            <option value="Abastecimento Unidade Núcleo Implante">Abastecimento Unidade Núcleo Implante</option>
                            <option value="Ajuste meia ampola">Ajuste meia ampola</option>
                            <option value="Avaria">Avaria</option>
                            <option value="Baixa Inventário Instituto Moema">Baixa Inventário Instituto Moema</option>
                            <option value="Baixa Inventário Instituto Tatuapé">Baixa Inventário Instituto Tatuapé</option>
                            <option value="Baixa Inventário Núcleo">Baixa Inventário Núcleo</option>
                            <option value="Baixa Inventário Estoque Central">Baixa Inventário Estoque Central</option>
                            <option value="Baixa Lidocaína">Baixa Lidocaína</option>
                            <option value="Devolução Fornecedor">Devolução Fornecedor</option>
                            <option value="Devolução empréstimo">Devolução empréstimo</option>
                            <option value="Empréstimo">Empréstimo</option>
                            <option value="Erro de Aspiração">Erro de Aspiração</option>
                            <option value="Fim de Plantão">Fim de Plantão</option>
                            <option value="Intercorrência De Soroterapia">Intercorrência De Soroterapia</option>
                            <option value="Lotes Bloqueados">Lotes Bloqueados</option>
                            <option value="Paciente levou a ampola">Paciente levou a ampola</option>
                            <option value="Paciente Recusou">Paciente Recusou</option>
                            <option value="Quebra">Quebra</option>
                            <option value="Vencido">Vencido</option>
                        </select>
                        <label for="motivo_baixa">Motivo de baixa:</label>
                    </div>
                </div>
                <div class="col-md-12 mt-4">
                    <div class="form-floating form-floating-outline mb-4">
                        <textarea class="form-control h-px-100" id="observacao" name='observacao'></textarea>
                        <label for="observacao">Observação:</label>
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
