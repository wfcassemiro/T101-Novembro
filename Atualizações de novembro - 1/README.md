# Sistema de Mapeamento de Palestras Hotmart ↔ Sistema T101

## 📋 Descrição

Interface web para associar manualmente os títulos de palestras da Hotmart com as palestras do sistema interno. Permite criar, visualizar e deletar mapeamentos que serão salvos diretamente no banco de dados na tabela `hotmart_lecture_mapping`.

## 📁 Arquivos Incluídos

1. **map_lectures_interface.php** - Interface principal de mapeamento
2. **data_hotmart.php** - Array com 560+ palestras da Hotmart (do arquivo txt)
3. **data_lectures.php** - Array com 170+ palestras do sistema (do arquivo xlsx)
4. **save_mapping_ajax.php** - Script AJAX para salvar associações no BD
5. **delete_mapping_ajax.php** - Script AJAX para deletar associações do BD
6. **README.md** - Este arquivo de documentação

## 🚀 Instalação

1. Copie todos os arquivos desta pasta para: `/public_html/v/admin/`

2. Acesse pelo navegador:
   ```
   https://seu-dominio.com/v/admin/map_lectures_interface.php
   ```

3. Faça login como administrador

## 💻 Como Usar

### Interface Principal

A interface está dividida em 3 colunas:

#### Coluna 1: Palestras Hotmart
- Lista todas as palestras da Hotmart em ordem alfabética
- Campo de busca para filtrar palestras
- Clique em uma palestra para selecioná-la (fica com fundo azul)

#### Coluna 2: Palestras do Sistema
- Lista todas as palestras do sistema em ordem alfabética
- Campo de busca para filtrar palestras
- Clique em uma palestra para selecioná-la (fica com fundo azul)

#### Coluna 3: Associações Criadas
- Mostra todas as associações já criadas
- Botão verde "Associar Selecionadas" (ativado quando você seleciona uma palestra de cada lado)
- Lista de associações existentes com botão de deletar

### Criar uma Associação

1. Clique em uma palestra na coluna "Palestras Hotmart"
2. Clique em uma palestra na coluna "Palestras do Sistema"
3. O botão "Associar Selecionadas" ficará habilitado
4. Clique no botão verde para salvar
5. A associação aparecerá imediatamente na coluna 3
6. Os dados são salvos na tabela `hotmart_lecture_mapping` do banco de dados

### Deletar uma Associação

1. Na coluna 3 (Associações Criadas), localize a associação que deseja remover
2. Clique no botão vermelho com ícone de lixeira
3. Confirme a exclusão na janela de confirmação
4. A associação será removida do banco de dados e da interface

### Buscar Palestras

- Use o campo de busca no topo de cada coluna
- Digite qualquer parte do título da palestra
- A lista será filtrada automaticamente conforme você digita

## 🗄️ Estrutura do Banco de Dados

### Tabela: `hotmart_lecture_mapping`

```sql
CREATE TABLE `hotmart_lecture_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotmart_title` varchar(500) NOT NULL,
  `lecture_id` varchar(36) NOT NULL,
  `lecture_title` varchar(500) NOT NULL,
  `hotmart_page_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos Salvos

- **id**: ID auto-incremento da associação
- **hotmart_title**: Título da palestra na Hotmart
- **lecture_id**: UUID da palestra no sistema
- **lecture_title**: Título da palestra no sistema
- **hotmart_page_id**: NULL (para uso futuro)

## 🎨 Recursos da Interface

- ✅ Design responsivo com Bootstrap 5
- ✅ Ordenação alfabética automática
- ✅ Busca em tempo real
- ✅ Feedback visual de seleções
- ✅ Alertas de sucesso/erro
- ✅ Confirmação antes de deletar
- ✅ Contadores de estatísticas
- ✅ Interface intuitiva de 3 colunas
- ✅ Sem necessidade de upload de arquivos

## 📊 Estatísticas

No topo da página você verá 3 caixas com:
1. **Palestras Hotmart**: Total de palestras da Hotmart (560+)
2. **Palestras Sistema**: Total de palestras do sistema (170+)
3. **Associações Criadas**: Número de mapeamentos já realizados

## 🔒 Segurança

- Requer login como administrador
- Validação de sessão em todos os arquivos
- Proteção contra SQL Injection (prepared statements)
- Validação de dados no backend
- Escape de HTML para prevenir XSS

## ⚠️ Notas Importantes

1. **Duplicatas**: O sistema não permite criar duas associações para a mesma palestra da Hotmart
2. **Permaneência**: Todas as associações são salvas permanentemente no banco de dados
3. **Performance**: A interface carrega todas as palestras de uma vez para máxima velocidade
4. **Sem Upload**: Os dados já estão incorporados nos arquivos PHP - não precisa fazer upload

## 🐛 Resolução de Problemas

### Erro "Não autorizado"
- Faça login como administrador

### Erro "Dados incompletos"
- Certifique-se de selecionar uma palestra de cada coluna

### Erro "Já existe uma associação"
- Essa palestra da Hotmart já foi mapeada
- Delete a associação existente primeiro se quiser refazer

### Interface não carrega
- Verifique se os arquivos estão no caminho correto
- Verifique permissões dos arquivos (644 para .php)
- Verifique se o banco de dados está acessível

## 📞 Suporte

Para problemas técnicos, verifique:
1. Logs do servidor em `/var/log/`
2. Console do navegador (F12) para erros JavaScript
3. Conexão com o banco de dados em `config/database.php`

## 🔄 Atualizações Futuras

Possíveis melhorias:
- Export de associações para CSV
- Import de associações em massa
- Histórico de alterações
- Campo de busca unificado
- Sugestões automáticas de correspondências

---

**Versão:** 1.0  
**Data:** Novembro 2025  
**Autor:** Sistema de Mapeamento T101
