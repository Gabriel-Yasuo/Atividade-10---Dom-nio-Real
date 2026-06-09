<?php

declare(strict_types=1);

namespace TaskMaster\Models;

use TaskMaster\Interfaces\Exibivel;
use TaskMaster\Interfaces\Persistivel;

/**
 * SubTarefa — COMPOSIÇÃO com Tarefa.
 * Não existe sem sua tarefa pai; é criada e destruída junto com ela.
 */
class SubTarefa implements Exibivel, Persistivel
{
    private static int $contador = 1;

    public function __construct(
        private readonly int $id,
        private string $descricao,
        private bool $concluida = false,
    ) {}

    public static function criar(string $descricao): self
    {
        return new self(self::$contador++, $descricao);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function isConcluida(): bool
    {
        return $this->concluida;
    }

    public function concluir(): void
    {
        $this->concluida = true;
    }

    // --- Exibivel ---

    public function exibir(): void
    {
        $icone = $this->concluida ? '[x]' : '[ ]';
        echo "      {$icone} #{$this->id} {$this->descricao}" . PHP_EOL;
    }

    public function resumo(): string
    {
        $status = $this->concluida ? 'concluída' : 'pendente';
        return "SubTarefa #{$this->id}: {$this->descricao} [{$status}]";
    }

    // --- Persistivel ---

    public function paraArray(): array
    {
        return [
            'id'        => $this->id,
            'descricao' => $this->descricao,
            'concluida' => $this->concluida,
        ];
    }

    public static function deArray(array $dados): static
    {
        $sub = new self($dados['id'], $dados['descricao'], $dados['concluida']);
        // Atualiza contador para evitar colisão de IDs ao carregar do disco
        if ($dados['id'] >= self::$contador) {
            self::$contador = $dados['id'] + 1;
        }
        return $sub;
    }
}
