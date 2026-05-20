# Plano de Implementação - Edição da Data de Lançamento do Pagamento

Permitir que o usuário edite a data de lançamento do pagamento diretamente na tela de edição do pagamento (`sistema/financeiros/editar_pagamento/{id}`).

## Análise e Verificação

Após análise do banco de dados e do código do sistema, confirmamos que:
- A data de lançamento de cada pagamento é controlada e armazenada na coluna `created_at` da tabela `financeiro_formas_pagamentos` (representada pelo Model `FinanceiroFormasPagamento`).
- Esta data (`created_at`) é utilizada para filtrar períodos no relatório financeiro, no fluxo de caixa (`RelatorioController`), e para ordenar cronologicamente a amortização e o rateio de procedimentos (`FinanceiroFormasPagamento::get_rateio_financeiro()`).
- O usuário solicitou que **não seja preservado o horário antigo**, mas sim que o sistema assuma a nova data combinada com o **horário atual da alteração** e que essa mudança de data seja registrada nos logs de procedimento.

---

## Proposta de Alterações

### [Componente: Visualização]

#### [MODIFY] [editar_pagamento.blade.php](file:///c:/xampp/htdocs/instituto_gl/resources/views/sistema/financeiros/editar_pagamento.blade.php)
* Adicionar a coluna **Data Lançamento** no cabeçalho e corpo da tabela de pagamentos.
* O campo será do tipo `date` (`<input type="date" required class="form-control" name="created_at" value="...">`), pré-preenchido com a data (YYYY-MM-DD) do atributo `created_at` atual do pagamento.

```html
<!-- Cabeçalho -->
<th>ID Pagamento / DOC</th>
<th>Data Lançamento</th>
<th>Valor</th>

<!-- Corpo -->
<td><input class="form-control" type="text" id="id_pagamento" name="id_pagamento" value="{{ $forma->id_pagamento }}"/></td>
<td><input required class="form-control" type="date" id="created_at" name="created_at" value="{{ date('Y-m-d', strtotime($forma->created_at)) }}"/></td>
<td><input required class="form-control valor" type="text" id="vl_pagamento" name="vl_pagamento" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($forma->vl_pagamento) }}"/></td>
```

---

### [Componente: Controller]

#### [MODIFY] [FinanceiroSistemaController.php](file:///c:/xampp/htdocs/instituto_gl/app/Http/Controllers/FinanceiroSistemaController.php)
* No método `update_pagamento(Request $request)`, receber o parâmetro `created_at`.
* Salvar a nova data combinando-a com o **horário atual (`date('H:i:s')`)**.
* Registrar a alteração com as datas antiga e nova no `ProcedimentoLog`.
* Recalcular o rateio de forma consistente com a nova data e horário.

```php
$data_pagamento_antiga = date('d/m/Y H:i:s', strtotime($forma->created_at));

if ($request->created_at) {
    $forma->created_at = $request->created_at . ' ' . date('H:i:s');
}

$data_pagamento_nova = date('d/m/Y H:i:s', strtotime($forma->created_at));
```

Log registrado:
```php
ProcedimentoLog::registrar($p->id, 'Financeiro', "Pagamento de R$ ".valorDbForm($vl_pagamento_antigo)." ($forma_pagamento_antiga) em $data_pagamento_antiga alterado para R$ ".valorDbForm($forma->vl_pagamento)." ($forma->forma_pagamento) em $data_pagamento_nova.");
```

---

## Plano de Verificação

### Testes Manuais
1. Acessar a tela de edição de um pagamento (ex: `/sistema/financeiros/editar_pagamento/{id}`).
2. Validar que o novo campo "Data Lançamento" é exibido com a data correta do pagamento.
3. Alterar a data do lançamento para outro dia e salvar.
4. Validar se o pagamento foi salvo com sucesso e se a data foi alterada no banco/tela de detalhes.
5. Confirmar se os logs de procedimento registram a alteração das datas com o novo horário.
