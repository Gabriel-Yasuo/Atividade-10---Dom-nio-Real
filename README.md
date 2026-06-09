# TaskMaster — CLI de Tarefas em PHP

Sistema de linha de comando para gerenciamento de tarefas do dia a dia, desenvolvido em PHP como projeto acadêmico para a disciplina de Programação Orientada a Objetos (ADS).

## Requisitos

- PHP 8.2 ou superior
- Composer

## Instalação

```bash
git clone https://github.com/seu-usuario/taskmaster.git
cd taskmaster
composer install
```

## Como usar

```bash
php taskmaster.php <comando> [argumentos]
```

### Tarefas

```bash
# Criar tarefa
php taskmaster.php tarefa:criar "Estudar PHP" "Revisar POO e interfaces" alta

# Listar todas
php taskmaster.php tarefa:listar

# Filtrar por status
php taskmaster.php tarefa:listar pendente

# Ver detalhes de uma tarefa
php taskmaster.php tarefa:ver 1

# Atualizar status
php taskmaster.php tarefa:status 1 "em andamento"

# Remover
php taskmaster.php tarefa:remover 1
```

### Sub-tarefas *(composição)*

```bash
# Adicionar sub-tarefa a uma tarefa
php taskmaster.php subtarefa:criar 1 "Ler capítulo de interfaces"

# Marcar sub-tarefa como concluída
php taskmaster.php subtarefa:concluir 1 1
```

### Usuários *(associação)*

```bash
# Criar usuário
php taskmaster.php usuario:criar "Gabriel" "gabriel@email.com"

# Listar usuários
php taskmaster.php usuario:listar

# Atribuir tarefa a um usuário
php taskmaster.php usuario:atribuir 1 1
```

### Categorias *(agregação)*

```bash
# Criar categoria
php taskmaster.php categoria:criar "Faculdade" azul

# Listar categorias
php taskmaster.php categoria:listar

# Adicionar tarefa a uma categoria
php taskmaster.php categoria:adicionar 1 1

# Listar tarefas de uma categoria
php taskmaster.php categoria:tarefas 1
```

### Relatório

```bash
php taskmaster.php relatorio
```

---

## Estrutura do projeto

```
taskmaster/
├── src/
│   ├── Interfaces/
│   │   ├── Exibivel.php       # contrato de exibição (polimorfismo)
│   │   └── Persistivel.php    # contrato de serialização
│   ├── Models/
│   │   ├── Tarefa.php         # entidade principal
│   │   ├── SubTarefa.php      # composição com Tarefa
│   │   ├── Usuario.php        # associação com Tarefa
│   │   └── Categoria.php      # agregação com Tarefa
│   └── Services/
│       └── GerenciadorTarefas.php  # orquestrador central
├── data/
│   └── dados.json             # persistência (gerado automaticamente)
├── taskmaster.php             # ponto de entrada CLI
├── composer.json
└── README.md
```

## Conceitos de POO aplicados

| Requisito | Onde está |
|---|---|
| `declare(strict_types=1)` | Todos os arquivos PHP |
| Atributos privados | Todos os models |
| Promotor de propriedades no construtor | `Tarefa`, `SubTarefa`, `Usuario`, `Categoria`, `GerenciadorTarefas` |
| Tipagem adequada | Parâmetros, retornos e propriedades tipados em todo o projeto |
| Associação | `Tarefa` ↔ `Usuario` (tarefa guarda `usuarioId`; existem independentemente) |
| Agregação | `Categoria` → `Tarefa` (categoria agrupa tarefas por ID; tarefas sobrevivem sem ela) |
| Composição | `Tarefa` → `SubTarefa` (sub-tarefas criadas dentro da tarefa; não existem sozinhas) |
| Interfaces | `Exibivel`, `Persistivel` |
| Polimorfismo | `exibir()` chamado uniformemente em `Tarefa`, `Usuario` e `Categoria` |
