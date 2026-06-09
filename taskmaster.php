#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use TaskMaster\Services\GerenciadorTarefas;

const ARQUIVO_DADOS = __DIR__ . '/data/dados.json';
const VERSAO        = '1.0.0';

$gerenciador = new GerenciadorTarefas(ARQUIVO_DADOS);

// ============================================================
// Leitura do comando
// ============================================================

$comando   = $argv[1] ?? 'ajuda';
$args      = array_slice($argv, 2);

// ============================================================
// Roteamento de comandos
// ============================================================

switch ($comando) {

    // ----- TAREFAS -----

    case 'tarefa:criar':
        // php taskmaster.php tarefa:criar "Título" "Descrição" [prioridade]
        $titulo    = $args[0] ?? '';
        $descricao = $args[1] ?? '';
        $prioridade = $args[2] ?? 'média';

        if ($titulo === '' || $descricao === '') {
            erro('Uso: tarefa:criar "Título" "Descrição" [baixa|média|alta|urgente]');
        }

        $tarefa = $gerenciador->criarTarefa($titulo, $descricao, $prioridade);
        sucesso("Tarefa #{$tarefa->getId()} criada: {$tarefa->getTitulo()}");
        break;

    case 'tarefa:listar':
        // php taskmaster.php tarefa:listar [status]
        $filtro = $args[0] ?? null;
        titulo('TAREFAS');
        $gerenciador->listarTarefas($filtro);
        break;

    case 'tarefa:ver':
        // php taskmaster.php tarefa:ver <id>
        $id = (int)($args[0] ?? 0);
        titulo("TAREFA #{$id}");
        $gerenciador->verTarefa($id);
        break;

    case 'tarefa:status':
        // php taskmaster.php tarefa:status <id> <status>
        $id     = (int)($args[0] ?? 0);
        $status = $args[1] ?? '';
        if ($id === 0 || $status === '') {
            erro('Uso: tarefa:status <id> <pendente|em andamento|concluída|cancelada>');
        }
        $ok = $gerenciador->atualizarStatusTarefa($id, $status);
        $ok ? sucesso("Status da tarefa #{$id} atualizado para '{$status}'.") : erro("Tarefa #{$id} não encontrada.");
        break;

    case 'tarefa:remover':
        // php taskmaster.php tarefa:remover <id>
        $id = (int)($args[0] ?? 0);
        $ok = $gerenciador->removerTarefa($id);
        $ok ? sucesso("Tarefa #{$id} removida.") : erro("Tarefa #{$id} não encontrada.");
        break;

    // ----- SUB-TAREFAS -----

    case 'subtarefa:criar':
        // php taskmaster.php subtarefa:criar <tarefaId> "Descrição"
        $tarefaId  = (int)($args[0] ?? 0);
        $descricao = $args[1] ?? '';
        if ($tarefaId === 0 || $descricao === '') {
            erro('Uso: subtarefa:criar <tarefaId> "Descrição"');
        }
        $sub = $gerenciador->adicionarSubTarefa($tarefaId, $descricao);
        $sub !== null
            ? sucesso("Sub-tarefa #{$sub->getId()} adicionada à tarefa #{$tarefaId}.")
            : erro("Tarefa #{$tarefaId} não encontrada.");
        break;

    case 'subtarefa:concluir':
        // php taskmaster.php subtarefa:concluir <tarefaId> <subId>
        $tarefaId = (int)($args[0] ?? 0);
        $subId    = (int)($args[1] ?? 0);
        $ok = $gerenciador->concluirSubTarefa($tarefaId, $subId);
        $ok ? sucesso("Sub-tarefa #{$subId} concluída.") : erro("Sub-tarefa ou tarefa não encontrada.");
        break;

    // ----- USUÁRIOS -----

    case 'usuario:criar':
        // php taskmaster.php usuario:criar "Nome" "email@exemplo.com"
        $nome  = $args[0] ?? '';
        $email = $args[1] ?? '';
        if ($nome === '' || $email === '') {
            erro('Uso: usuario:criar "Nome" "email@exemplo.com"');
        }
        $usuario = $gerenciador->criarUsuario($nome, $email);
        sucesso("Usuário #{$usuario->getId()} criado: {$usuario->getNome()}");
        break;

    case 'usuario:listar':
        titulo('USUÁRIOS');
        $gerenciador->listarUsuarios();
        break;

    case 'usuario:atribuir':
        // php taskmaster.php usuario:atribuir <tarefaId> <usuarioId>
        $tarefaId  = (int)($args[0] ?? 0);
        $usuarioId = (int)($args[1] ?? 0);
        if ($tarefaId === 0 || $usuarioId === 0) {
            erro('Uso: usuario:atribuir <tarefaId> <usuarioId>');
        }
        $ok = $gerenciador->atribuirTarefa($tarefaId, $usuarioId);
        $ok ? sucesso("Usuário #{$usuarioId} atribuído à tarefa #{$tarefaId}.") : erro("Tarefa ou usuário não encontrado.");
        break;

    // ----- CATEGORIAS -----

    case 'categoria:criar':
        // php taskmaster.php categoria:criar "Nome" [cor]
        $nome = $args[0] ?? '';
        $cor  = $args[1] ?? 'branco';
        if ($nome === '') {
            erro('Uso: categoria:criar "Nome" [cor]');
        }
        $cat = $gerenciador->criarCategoria($nome, $cor);
        sucesso("Categoria #{$cat->getId()} criada: {$cat->getNome()}");
        break;

    case 'categoria:listar':
        titulo('CATEGORIAS');
        $gerenciador->listarCategorias();
        break;

    case 'categoria:adicionar':
        // php taskmaster.php categoria:adicionar <tarefaId> <categoriaId>
        $tarefaId    = (int)($args[0] ?? 0);
        $categoriaId = (int)($args[1] ?? 0);
        if ($tarefaId === 0 || $categoriaId === 0) {
            erro('Uso: categoria:adicionar <tarefaId> <categoriaId>');
        }
        $ok = $gerenciador->categorizarTarefa($tarefaId, $categoriaId);
        $ok ? sucesso("Tarefa #{$tarefaId} adicionada à categoria #{$categoriaId}.") : erro("Tarefa ou categoria não encontrada.");
        break;

    case 'categoria:tarefas':
        // php taskmaster.php categoria:tarefas <categoriaId>
        $categoriaId = (int)($args[0] ?? 0);
        titulo("TAREFAS DA CATEGORIA #{$categoriaId}");
        $gerenciador->listarTarefasPorCategoria($categoriaId);
        break;

    // ----- GERAL -----

    case 'relatorio':
        $gerenciador->relatorio();
        break;

    case 'ajuda':
    default:
        ajuda();
        break;
}

// ============================================================
// Funções auxiliares de saída
// ============================================================

function titulo(string $texto): void
{
    echo PHP_EOL . "  === {$texto} ===" . PHP_EOL . PHP_EOL;
}

function sucesso(string $mensagem): void
{
    echo PHP_EOL . "  [OK] {$mensagem}" . PHP_EOL . PHP_EOL;
}

function erro(string $mensagem): never
{
    echo PHP_EOL . "  [ERRO] {$mensagem}" . PHP_EOL . PHP_EOL;
    exit(1);
}

function ajuda(): void
{
    echo <<<AJUDA

  ╔══════════════════════════════════════════════════╗
  ║         TaskMaster v1.0.0 — CLI de Tarefas      ║
  ╚══════════════════════════════════════════════════╝

  TAREFAS
    tarefa:criar "Título" "Descrição" [prioridade]
    tarefa:listar [status]
    tarefa:ver <id>
    tarefa:status <id> <novo-status>
    tarefa:remover <id>

  SUB-TAREFAS  (composição — vivem dentro da tarefa)
    subtarefa:criar <tarefaId> "Descrição"
    subtarefa:concluir <tarefaId> <subId>

  USUÁRIOS  (associação — existem independentemente)
    usuario:criar "Nome" "email@exemplo.com"
    usuario:listar
    usuario:atribuir <tarefaId> <usuarioId>

  CATEGORIAS  (agregação — agrupam tarefas)
    categoria:criar "Nome" [cor]
    categoria:listar
    categoria:adicionar <tarefaId> <categoriaId>
    categoria:tarefas <categoriaId>

  GERAL
    relatorio
    ajuda

  PRIORIDADES: baixa | média | alta | urgente
  STATUS:      pendente | em andamento | concluída | cancelada

AJUDA;
}
