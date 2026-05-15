# Relatório de Implementações e Pesquisa - Instituto GL

Este documento resume as melhorias realizadas no sistema financeiro e a pesquisa técnica para a integração com a API da Kamino.

## 1. Melhorias nos Relatórios de Caixa

### 1.1 Relatório de Caixa Diário (Individual)
- **Nova Coluna:** Adicionado o campo **"Nº DOC"** (ID do Pagamento) para facilitar a conferência de comprovantes.
- **Campos de Assinatura:** Inseridas linhas para "Assinatura do Colaborador (Entrega)" e "Assinatura do Responsável (Recebimento)" ao final do relatório impresso.

### 1.2 Novo Relatório de Caixa Geral (Administrativo)
- **Localização:** Disponível em **Relatórios > Caixa** no menu administrativo.
- **Funcionalidade:** Permite filtrar pagamentos por **Clínica**, **Colaborador** e **Período** (Data Início/Fim).
- **Detalhamento:** Exibe data/hora, quem registrou o pagamento, paciente, valor, forma de pagamento e Nº DOC.
- **Assinaturas:** Também inclui os campos para formalização da entrega de valores.

---

## 2. Integração com API Kamino (Fintech Brasileira)

### 2.1 Ambiente de Sandbox e Autenticação
- **Confirmação:** Validamos que o sistema atual possui as credenciais (App, CN, Hash, Usr) válidas.
- **Sandbox:** O ambiente de testes em `https://sandbox.kamino.tech` foi testado e está respondendo corretamente às credenciais de produção.

### 2.2 Endpoint de Contas a Receber (Recebimentos)
Foi identificado que é possível automatizar o lançamento de cobranças através da API:
- **Endpoint:** `POST /api/financeiro/recebimento`
- **Campos de Valor e Datas:**
    - `VlrVenc`: Valor nominal do título.
    - `VlrBruto`: Valor total (incluindo taxas/juros se houver).
    - `DtaVenc`: Data de vencimento (`AAAA-MM-DD`).
    - `DtaCompet`: Data de competência contábil.
- **Campos de Identificação:**
    - `IDPessoa`: Identificador do paciente na Kamino (ID interno).
    - `Descri`: Texto descritivo (aparece no extrato/boleto).
    - `CodigoExterno`: ID de referência do seu sistema.
    - `NroNotaFiscal`: Número da NF vinculada.
- **Campos de Status e Pagamento:**
    - `SitConta`: 1 (Pendente) ou 2 (Paga).
    - `VlrPagto`: Valor efetivamente pago (se a situação for 2).
    - `DtaPagto`: Data em que o pagamento ocorreu.
    - `VlrJuros` / `VlrTaxa` / `VlrDescReceb`: Detalhamento de acréscimos ou descontos.

### 2.3 Estratégia de Identificação por CPF
- Como o sistema não armazena o ID interno da Kamino, a integração utilizará o **CPF** do paciente para buscar o ID correspondente na Kamino antes de realizar qualquer lançamento financeiro.
- Se o paciente não for encontrado pelo CPF, o sistema terá a capacidade de cadastrá-lo automaticamente antes de gerar a conta a receber.

### 2.4 Campos de Classificação e Automação
Para uma organização financeira completa, podemos enviar os IDs internos da Kamino:
- `IDUnidadeNegocio`: Para separar por unidade/clínica.
- `IDCentroCusto`: Para separar por setor ou departamento.
- `IDPlanoContaOrigem`: Categoria da receita (ex: "Venda de Serviços").
- `IDPlanoContaPagto`: Conta bancária de destino (para contas já pagas).
- `GerarCobranca`: Se enviado como `true`, a Kamino pode disparar a geração do boleto/cobrança automaticamente.

---

## 3. Exemplos de Código para Testes (Kamino)

Os scripts abaixo foram utilizados para validar a conexão e podem ser usados como referência para a implementação definitiva.

### 3.1 Script de Teste de Conexão (Sandbox)
```php
// Endpoint: https://sandbox.kamino.tech/api/pessoa/lista/paginada
$headers = [
    'App' => 'bd396410-d4b5-477a-add7-c0afe9a445f3',
    'CN' => 'InstitutoGL5231',
    'Hash' => '...', // Omitido por segurança no log, mas presente no código
    'IDUsr' => '2',
    'Usr' => 'f6e09b8e-c05b-4779-a32a-4c4897afb498',
];
// Retorna Status 200 se as credenciais estiverem corretas.
```

### 3.2 Script de Lançamento de Recebimento
```php
// Endpoint: POST https://sandbox.kamino.tech/api/financeiro/recebimento
$body = [
    'ID' => 0,
    'IDPessoa' => 563, // ID obtido via busca por CPF
    'VlrVenc' => 150.00,
    'VlrBruto' => 150.00,
    'DtaVenc' => '2026-06-14',
    'SitConta' => 1,
    'Descri' => 'Teste de Conta a Receber - Integracao GL',
    'CodigoExterno' => 'TESTE-1778853503'
];
// Retorna Sucesso: true e o ID do novo título na Kamino.
```

---
*Documento gerado em 15/05/2026*
