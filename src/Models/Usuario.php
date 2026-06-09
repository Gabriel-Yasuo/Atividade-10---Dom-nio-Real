<?php

declare(strict_types=1);

namespace TaskMaster\Models;

use TaskMaster\Interfaces\Exibivel;
use TaskMaster\Interfaces\Persistivel;

/**
 * Usuario — ASSOCIAÇÃO com Tarefa.
 * Existe independentemente das tarefas; pode ser atribuído a várias delas.
 */
class Usuario implements Exibivel, Persistivel
{
    private static int $contador = 1;

    /** @var int[] IDs das tarefas associadas a este usuário */
    private array $tarefasIds = [];

    public function __construct(
        private readonly int $id,
        private string $nome,
        private string $email,
    ) {}

    public static function criar(string $nome, string $email): self
    {
        return new self(self::$contador++, $nome, $email);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function adicionarTarefaId(int $tarefaId): void
    {
        if (!in_array($tarefaId, $this->tarefasIds, true)) {
            $this->tarefasIds[] = $tarefaId;
        }
    }

    /** @return int[] */
    public function getTarefasIds(): array
    {
        return $this->tarefasIds;
    }

    // --- Exibivel ---

    public function exibir(): void
    {
        $total = count($this->tarefasIds);
        echo "  Usuario #{$this->id}" . PHP_EOL;
        echo "    Nome : {$this->nome}" . PHP_EOL;
        echo "    Email: {$this->email}" . PHP_EOL;
        echo "    Tarefas atribuídas: {$total}" . PHP_EOL;
    }

    public function resumo(): string
    {
        return "#{$this->id} {$this->nome} <{$this->email}>";
    }

    // --- Persistivel ---

    public function paraArray(): array
    {
        return [
            'id'        => $this->id,
            'nome'      => $this->nome,
            'email'     => $this->email,
            'tarefasIds'=> $this->tarefasIds,
        ];
    }

    public static function deArray(array $dados): static
    {
        $u = new self($dados['id'], $dados['nome'], $dados['email']);
        $u->tarefasIds = $dados['tarefasIds'] ?? [];
        if ($dados['id'] >= self::$contador) {
            self::$contador = $dados['id'] + 1;
        }
        return $u;
    }
}
