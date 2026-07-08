# Relatório Técnico: Investigação de Incidente de Exclusão de Semana Aplicada
**Caso:** Paciente Giovana Santos Renovato
**Código do Grupo:** 2253920260617174235
**Data do Incidente:** 03/07/2026
**Data do Relatório:** 08/07/2026

---

## 📋 1. Resumo do Incidente

No dia **03/07/2026**, a equipe relatou um comportamento inesperado ("o sistema ficou louco") após a exclusão de semanas de tratamento para a paciente Giovana. O relato indicava que, ao tentar remover a última semana do tratamento, a **Semana 1** (que já havia sido realizada/aplicada) sumiu/foi excluída.

Este relatório traz a reconstituição exata dos fatos com base nos logs de auditoria do banco de dados (`procedimento_logs`), demonstrando que **não houve falha de exclusão aleatória pelo sistema**, mas sim um **desalinhamento visual de reordenação automática** (causado por um bug de ordenação cronológica com semanas em atraso), o que induziu as colaboradoras ao erro.

---

## 🔍 2. O Cenário Inicial

Quando o tratamento foi criado em **17/06/2026**, foram gerados **12 procedimentos** (semanas):
- **Semanas com múltiplos medicamentos** (NAC, Curcumina, Coenzima, etc.): Semanas 1, 3, 5, 7, 9, 11.
- **Semanas apenas com Mounjaro**: Semanas 2 (`ID 37521`), 4 (`ID 37523`) e 12 (`ID 37531`).
- **A Semana 1 original** (`ID 37520`) continha todos os medicamentos da semana 1 e foi agendada para **17/06/2026**.

---

## 🕒 3. Cronologia Exata dos Fatos (Logs de Auditoria de 03/07/2026)

Abaixo está a linha do tempo exata das ações realizadas no painel do sistema no dia 03/07/2026:

### Passo 1: Aplicação da Semana 1
* **Horário:** `14:45:50`
* **Ação:** A enfermagem aplicou a **Semana 1** (`ID 37520`).
* **Comportamento do Sistema:** A situação mudou para **"Aplicado"** e a data do procedimento (`data_aplicacao`) foi atualizada de `17/06/2026` para a data real de aplicação: **03/07/2026** (16 dias de atraso).

### Passo 2: Remoção do Mounjaro
* **Horário:** `15:09:41`
* **Ação:** A equipe editou a Semana 1 aplicada (`ID 37520`) e **removeu apenas o Mounjaro 90mg**, mantendo os demais medicamentos aplicados.

### Passo 3: Exclusão da Semana 2
* **Horário:** `15:09:51`
* **Ação:** A equipe **excluiu a Semana 2** (`ID 37521` - que continha apenas Mounjaro).
* **O Bug Silencioso:** A exclusão disparou a função do sistema para reordenar consecutivamente as semanas (1, 2, 3, 4...).
  - A Semana 3 (`ID 37522`) estava agendada para **01/07/2026** (não realizada).
  - A Semana 1 (`ID 37520`), já realizada, tinha data de **03/07/2026** (data real da aplicação).
  - O sistema ordenou os registros por data de forma crescente. Como **01/07** (Semana 3) é anterior a **03/07** (Semana 1), o sistema colocou a Semana 3 **antes** da Semana 1!
  - **Resultado:** A Semana 3 (`ID 37522`) virou "Semana 1" (não aplicada) e a Semana 1 original (`ID 37520`) virou "Semana 2" (aplicada).

### Passo 4: Confusão Visual e Edição
* **Horário:** `15:11:17`
* **Ação:** A equipe olhou a tela e viu a linha com o rótulo "Semana 1" como **não aplicada** (porque na verdade era a antiga Semana 3 que herdou o número 1). Confusas, editaram essa nova "Semana 1" (`ID 37522`) tirando e pondo o Mounjaro para testar.

### Passo 5: Exclusão da Semana 12
* **Horário:** `16:54:22`
* **Ação:** A equipe excluiu a **Semana 12** (`ID 37531`). Isso disparou o recálculo de numeração novamente, mantendo a ordem incorreta.

### Passo 6: Exclusão Acidental da Semana Aplicada
* **Horário:** `16:54:32`
* **Ação:** A equipe, tentando remover o restante das semanas de Mounjaro, clicou em **Excluir** na linha que aparecia na tela como **"Semana 2"** (que na verdade era a **Semana 1 original aplicada** `ID 37520`!).
* **Resultado:** O sistema permitiu a exclusão física do registro `37520`. Toda a aplicação e o histórico de medicamentos aplicados da Semana 1 foram apagados do banco.

---

## 📈 4. O Impacto Visual que Gerou a Confusão

| ID do Banco | Nome Original (Criado) | Data de Agendamento/Aplicação | Situação Real | Nome na Tela (Após Passo 3) |
| :--- | :--- | :--- | :--- | :--- |
| **37522** | Semana 3 | 01/07/2026 | Agendado (Não Aplicado) | **Semana 1** |
| **37520** | Semana 1 | 03/07/2026 | **Aplicado** | **Semana 2** |

Como a antiga Semana 3 herdou o número **1** e aparecia como não aplicada, a equipe acreditou que o sistema havia "desaplicado" ou "apagado" a aplicação. Ao tentarem ajustar o painel excluindo as linhas de Mounjaro, acabaram clicando em excluir na "Semana 2" (que continha a aplicação real da Semana 1).

---

## 🛠️ 5. Como o Sistema foi Corrigido para Evitar isso no Futuro

Para que essa situação nunca mais ocorra com nenhum paciente, aplicamos duas travas de segurança na branch `dev`:

1. **Ordenação Inteligente (`recalcular_semanas_grupo`)**:
   A ordenação automática foi corrigida. Agora ela separa as semanas em dois grupos antes de numerá-las:
   - **Primeiro grupo:** Semanas já **Aplicadas, Parciais, Pendentes ou em Atendimento** (ficam sempre no topo da fila e na ordem em que aconteceram).
   - **Segundo grupo:** Semanas **Agendadas** (futuras/não aplicadas), ordenadas cronologicamente.
   
   Isso garante que uma semana aplicada tardiamente **nunca** seja jogada para baixo e trocada de lugar com uma semana não aplicada.

2. **Bloqueio de Exclusão Física (Backend e Frontend)**:
   - **Na tela**: O botão "Excluir" agora fica oculto caso a semana já esteja marcada como `Aplicado`, `Aplicação Parcial`, `Pendente`, `Atendimento` ou tenha registro de medicamentos já aplicados.
   - **No servidor**: Adicionamos um bloqueio rígido. Mesmo se alguém tentar acessar a tela de exclusão digitando o link direto no navegador, o servidor recusará e retornará a mensagem: *"Não é possível excluir um procedimento/semana que já foi aplicado ou está em atendimento."*

---

## 💡 6. Recomendação para Reunião com a Equipe

1. **Esclarecer o Mal-Entendido**: Mostrar a elas que a Semana 1 não "sumiu sozinha" e sim **mudou de nome temporariamente para Semana 2** devido a ordenação por datas, o que as levou a excluir a linha errada por engano.
2. **Reforçar a Nova Trava**: Garantir a elas que agora o sistema está protegido e elas não conseguirão mais apagar acidentalmente nenhuma semana que já tenha sido aplicada, nem de forma acidental.

---

## 🛠️ 7. Script de Restauração (Como Executar em Produção)

Para restabelecer as semanas deletadas ao estado exato antes da exclusão, foi criado o script `restore_giovana.php` na raiz do projeto. O script foi homologado com sucesso no banco de dados de desenvolvimento (`dev`) em 08/07/2026.

### Como o script reconstrói os dados:
1. **Replay Inteligente de Logs**: Ele analisa a tabela `procedimento_logs` cronologicamente para remontar cada semana.
2. **Filtro de Cascata**: O script identifica o log de `'Exclusão'` de cada semana e descarta todas as remoções de medicamentos feitas de forma automática em cascata pelo Laravel durante a deleção. Isso garante que a Semana 1 seja restaurada com NAC, Curcumina, Coenzima e Vitamina D aplicados, e as Semanas 2, 4 e 12 com Mounjaro pendente.
3. **Compatibilidade de Banco**: O script lida com a falta de auto-incremento (comum em dumps clonados) gerando manualmente IDs incrementais para as tabelas `aplicacaos`, `aplicacao_lotes` e `estoques`.

### Instruções para Execução em Produção:
1. **Ver Prévia (Dry-run)**:
   ```bash
   php restore_giovana.php
   ```
2. **Executar Restauração Efetiva**:
   ```bash
   php restore_giovana.php --execute
   ```

