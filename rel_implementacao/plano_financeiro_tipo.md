# Plano de Implementação - Ajuste no Tipo de Lançamento do Relatório Financeiro

Ajustar a forma de rateio dos pagamentos no Relatório Financeiro para que, além de diferenciar a **Consulta** e a **Aplicação**, também diferencie itens do tipo **Procedimento** (quando a unidade do medicamento for `"Procedimento"`).

## Proposta de Alterações

### [Componente: Model de Formas de Pagamento]

#### [MODIFY] [FinanceiroFormasPagamento.php](file:///c:/xampp/htdocs/instituto_gl/app/Models/FinanceiroFormasPagamento.php)
*   Modificar o método `get_rateio_financeiro()` para:
    1. Obter o valor total dos itens do tipo "Aplicação" (unidade diferente de `"Procedimento"`) usando o método `valor_aplicacaos()` do Financeiro.
    2. Obter o valor total dos itens do tipo "Procedimento" (unidade igual a `"Procedimento"`) usando o método `valor_procedimentos()` do Financeiro.
    3. Tratar proporcionalmente a distribuição do valor pago (após amortizar a Consulta) entre o saldo de Aplicação e o saldo de Procedimento, evitando favorecer um em detrimento do outro quando ambos estão presentes.
    4. Adicionar as chaves `vl_procedimento` e `tipo_procedimento` ao array de retorno.

---

### [Componente: Relatório Controller]

#### [MODIFY] [RelatorioController.php](file:///c:/xampp/htdocs/instituto_gl/app/Http/Controllers/RelatorioController.php)
*   No método `financeiro_gerar`, adicionar um novo bloco condicional para verificar se `vl_procedimento > 0`. Caso seja positivo, criar uma linha de dados com `tp_pagamento` setado como `'Procedimento'`.
*   No método `exportar_financeiro`, adicionar a mesma condicional para incluir linhas do tipo `'Procedimento'` na exportação para planilha Excel.

---

## Plano de Verificação

### Testes Manuais
1. Acessar a geração do relatório financeiro.
2. Validar se pagamentos de procedimentos com medicamentos cuja unidade é `"Procedimento"` agora aparecem listados com a coluna Tipo contendo **"Procedimento"** (e não mais "Aplicação").
3. Validar se registros com medicamentos cuja unidade é `"Ampola"` ou `"Miligrama"` continuam aparecendo como **"Aplicação"**.
4. Validar se a exportação em Excel reflete os mesmos dados.
