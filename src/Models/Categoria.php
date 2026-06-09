<?php

declare(strict_types=1);

namespace TaskMaster\Models;

use TaskMaster\Interfaces\Exibivel;
use TaskMaster\Interfaces\Persistivel;

/**
 * Categoria — AGREGAÇÃO com Tarefa.
 * Agrupa tarefas, mas as tarefas existem independentemente da categoria.
 * Uma tarefa pode existir sem categoria; uma categoria pode ser excluída
 * sem que suas tarefas sejam destruídas.
 */
class Categoria implements Exibivel, Persistivel
{
    private static int $contador = 1;

    /** @var int[] IDs das tarefas que pertencem a esta categoria (agregação) */
    private array $tarefasIds = [];

    public function __construct(
        private readonly int $id,
        private string $nome,
        private string $cor,
    ) {}

    public static function criar(string $nome, string $cor = 'branco'): self
    {
        return new self(self::$contador++, $nome, $cor);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getCor(): string
    {
        return $this->cor;
    }

    /** AGREGAÇÃO: adiciona referência a uma tarefa já existente */
    public function adicionarTarefa(Tarefa $tarefa): void
    {
        if (!in_array($tarefa->getId(), $this->tarefasIds, true)) {
            $this->tarefasIds[] = $tarefa->getId();
        }
    }

    public function removerTarefa(int $tarefaId): void
    {
        $this->tarefasIds = array_values(
            array_filter($this->tarefasIds, fn(int $id) => $id !== $tarefaId)
        );
    }

    public function contemTarefa(int $tarefaId): bool
    {
        return in_array($tarefaId, $this->tarefasIds, true);
    }

    /** @return int[] */
    public function getTarefasIds(): array
    {
        return $this->tarefasIds;
    }

    public function totalTarefas(): int
    {
        return count($this->tarefasIds);
    }

    // --- Exibivel ---

    public function exibir(): void
    {
        $total = $this->totalTarefas();
        echo "  Categoria #{$this->id}: {$this->nome} (cor: {$this->cor}) — {$total} tarefa(s)" . PHP_EOL;
    }

    public function resumo(): string
    {
        return "#{$this->id} {$this->nome} [{$this->cor}] ({$this->totalTarefas()} tarefas)";
    }

    // --- Persistivel ---

    public function paraArray(): array
    {
        return [
            'id'        => $this->id,
            'nome'      => $this->nome,
            'cor'       => $this->cor,
            'tarefasIds'=> $this->tarefasIds,
        ];
    }

    public static function deArray(array $dados): static
    {
        $cat = new self($dados['id'], $dados['nome'], $dados['cor']);
        $cat->tarefasIds = $dados['tarefasIds'] ?? [];
        if ($dados['id'] >= self::$contador) {
            self::$contador = $dados['id'] + 1;
        }
        return $cat;
    }
}
