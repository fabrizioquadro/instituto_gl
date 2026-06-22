# Plano de Implementação: Correção das Datas do Relatório de Enfermagem

**Branch**: `001-fix-nursing-report-dates` | **Data**: 19/06/2026 | **Especificação**: [spec.md](file:///c:/xampp/htdocs/instituto_gl/specs/001-fix-nursing-report-dates/spec.md)

## Resumo

Este plano visa corrigir o agrupamento incorreto de datas de aplicação de medicamentos no Relatório de Enfermagem (tela e Excel). A consulta passará a realizar um `JOIN` com a tabela `aplicacaos` e filtrar pelo campo `updated_at` da aplicação do medicamento. Os loops de exibição na view blade e no controller serão atualizados para validar o período de exibição de cada linha, e adicionaremos uma nova coluna de "Aplicação" contendo a data/hora exata do procedimento aplicado.

## Contexto Técnico

**Linguagem/Versão**: PHP 8.1 / Laravel 10.10

**Principais Dependências**: Laravel Framework, PhpSpreadsheet (para exportação Excel)

**Armazenamento**: MySQL (tabelas `procedimentos`, `aplicacaos`)

**Testes**: Verificação manual através da tela de relatório (`/adm/relatorios/enfermagem`) e validação da planilha exportada.

**Tipo de Projeto**: Aplicação Web MVC (Laravel)

## Estrutura do Projeto

### Documentação (esta funcionalidade)

```text
specs/001-fix-nursing-report-dates/
├── spec.md              # Especificação de requisitos (em Português)
├── plan.md              # Este plano de implementação (em Português)
├── research.md          # Pesquisa técnica e alternativas (em Português)
├── data-model.md        # Entidades e campos afetados (em Português)
└── quickstart.md        # Roteiro de validação rápida (em Português)
```

### Arquivos de Código-Fonte Modificados

```text
app/
├── Models/
│   └── Procedimento.php                       # Atualização do método de consulta gerar_relatorio_enfermagem
└── Http/
    └── Controllers/
        └── RelatorioController.php            # Atualização do método de exportação exportar_enfermagem

resources/
└── views/
    └── adm/
        └── relatorios/
            └── enfermagem_gerar.blade.php     # Exibição da coluna de data de aplicação e filtragem no loop
```

---

## Detalhes das Alterações Propostas

### 1. [Procedimento.php](file:///c:/xampp/htdocs/instituto_gl/app/Models/Procedimento.php)
Modificar o método `gerar_relatorio_enfermagem` para unir a tabela de `aplicacaos` e aplicar o filtro de datas com base em `aplicacaos.updated_at` (onde `aplicacaos.situacao = 'Aplicada'`).

### 2. Migration e Banco de Dados [NEW]
Adicionar colunas `dt_hr_chegada` e `dt_hr_atendimento` na tabela de `aplicacaos` (ambas `dateTime` e `nullable`). Registrar as novas colunas no array `$fillable` do model [Aplicacao.php](file:///c:/xampp/htdocs/instituto_gl/app/Models/Aplicacao.php).

### 3. [DashboardSistemaController.php](file:///c:/xampp/htdocs/instituto_gl/app/Http/Controllers/DashboardSistemaController.php)
No método `set_aplicacao`, copiar as datas `dt_hr_chegada` e `dt_hr_atendimento` do `$procedimento` para o registro de `$aplicacao` individual no momento de registrá-la como `'Aplicada'`.

### 4. [RelatorioController.php](file:///c:/xampp/htdocs/instituto_gl/app/Http/Controllers/RelatorioController.php)
* No método `exportar_enfermagem`, filtrar cada linha do loop para verificar se a data de atualização da aplicação (`updated_at`) está contida dentro do intervalo de busca (`dt_inc` e `dt_fn`).
* Adicionar a coluna "Aplicação" no cabeçalho e popular a respectiva célula com a data/hora real de aplicação do medicamento.
* Atualizar a exportação das colunas de Chegada e Atendimento para buscar os dados da `$aplicacao` individual, caindo de volta para a data de aplicação (`$aplicacao->updated_at`) como fallback quando nulos.

### 5. [enfermagem_gerar.blade.php](file:///c:/xampp/htdocs/instituto_gl/resources/views/adm/relatorios/enfermagem_gerar.blade.php)
* Adicionar o cabeçalho `<th>Aplicação</th>` após a coluna de finalização.
* No loop das aplicações de cada procedimento, filtrar as linhas pela data de aplicação individual em relação às datas de filtro informadas.
* Renderizar a coluna correspondente no formato `d/m/Y H:i:s`.
* Exibir os horários de Chegada e Atendimento a nível de `$aplicacao` individual, recorrendo à data/hora de aplicação (`$aplicacao->updated_at`) como fallback se nulos.

---

## Plano de Verificação

### Testes Manuais
- Seguir os cenários de validação especificados no arquivo [quickstart.md](file:///c:/xampp/htdocs/instituto_gl/specs/001-fix-nursing-report-dates/quickstart.md) para garantir que:
  1. Aplicações em datas distintas no mesmo procedimento sejam filtradas corretamente por data no relatório.
  2. A data/hora exata de cada aplicação seja exibida na tela e no arquivo Excel exportado.
