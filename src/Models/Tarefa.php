<?php

declare(strict_types=1);

namespace TaskMaster\Models;

use TaskMaster\Interfaces\Exibivel;
use TaskMaster\Interfaces\Persistivel;

enum Prioridade: string
{
    case Baixa  = 'baixa';
    case Media  = 'média';
    case Alta   = 'alta';
    case Urgente = 'urgente';

    public function icone(): string
    {
        return match($this) {
            self::Baixa   => '[.]',
            self::Media   => '[~]',
            self::Alta    => '[!]',
            self::Urgente => '[!!]',
        };
    }
}

enum StatusTarefa: string
{
    case Pendente    = 'pendente';
    case EmAndamento = 'em andamento';
    case Concluida   = 'concluída';
    case Cancelada   = 'cancelada';
}

/**
 * Tarefa — entidade principal.
 *
 * COMPOSIÇÃO: contém SubTarefas (não existem sem a Tarefa).
 * ASSOCIAÇÃO: referencia um Usuario pelo ID (existe independente).
 */
class Tarefa implements Exibivel, Persistivel
{
    private static int $contador = 1;

    /** @var SubTarefa[] COMPOSIÇÃO — sub-tarefas pertencem exclusivamente a esta tarefa */
    private array $subTarefas = [];

    /** ASSOCIAÇÃO — apenas referência ao ID do usuário responsável */
    private ?int $usuarioId = null;

    public function __construct(
        private readonly int $id,
        private string $titulo,
        private string $descricao,
        private Prioridade $prioridade,
        private StatusTarefa $status,
        private readonly string $dataCriacao,
    ) {}

    public static function criar(
        string $titulo,
        string $descricao,
        Prioridade $prioridade = Prioridade::Media,
    ): self {
        return new self(
            id: self::$contador++,
            titulo: $titulo,
            descricao: $descricao,
            prioridade: $prioridade,
            status: StatusTarefa::Pendente,
            dataCriacao: date('d/m/Y H:i'),
        );
    }

    // --- Getters ---

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function getStatus(): StatusTarefa
    {
        return $this->status;
    }

    public function getPrioridade(): Prioridade
    {
        return $this->prioridade;
    }

    public function getUsuarioId(): ?int
    {
        return $this->usuarioId;
    }

    /** @return SubTarefa[] */
    public function getSubTarefas(): array
    {
        return $this->subTarefas;
    }

    // --- Ações ---

    public function atualizarStatus(StatusTarefa $novoStatus): void
    {
        $this->status = $novoStatus;
    }

    public function atualizarPrioridade(Prioridade $novaPrioridade): void
    {
        $this->prioridade = $novaPrioridade;
    }

    public function atribuirUsuario(int $usuarioId): void
    {
        $this->usuarioId = $usuarioId;
    }

    /** COMPOSIÇÃO: adiciona sub-tarefa — ela passa a ser "parte" desta tarefa */
    public function adicionarSubTarefa(string $descricao): SubTarefa
    {
        $sub = SubTarefa::criar($descricao);
        $this->subTarefas[] = $sub;
        return $sub;
    }

    public function concluirSubTarefa(int $subId): bool
    {
        foreach ($this->subTarefas as $sub) {
            if ($sub->getId() === $subId) {
                $sub->concluir();
                return true;
            }
        }
        return false;
    }

    public function progressoSubTarefas(): string
    {
        $total = count($this->subTarefas);
        if ($total === 0) {
            return 'sem sub-tarefas';
        }
        $concluidas = count(array_filter($this->subTarefas, fn(SubTarefa $s) => $s->isConcluida()));
        return "{$concluidas}/{$total}";
    }

    // --- Exibivel ---

    public function exibir(): void
    {
        $prioridade = $this->prioridade->icone() . ' ' . strtoupper($this->prioridade->value);
        $status     = strtoupper($this->status->value);
        $responsavel = $this->usuarioId !== null ? "Usuario #{$this->usuarioId}" : 'não atribuída';

        echo str_repeat('-', 50) . PHP_EOL;
        echo "  Tarefa #{$this->id}: {$this->titulo}" . PHP_EOL;
        echo "  Descrição  : {$this->descricao}" . PHP_EOL;
        echo "  Prioridade : {$prioridade}" . PHP_EOL;
        echo "  Status     : {$status}" . PHP_EOL;
        echo "  Criada em  : {$this->dataCriacao}" . PHP_EOL;
        echo "  Responsável: {$responsavel}" . PHP_EOL;

        if (!empty($this->subTarefas)) {
            $progresso = $this->progressoSubTarefas();
            echo "  Sub-tarefas: ({$progresso} concluídas)" . PHP_EOL;
            foreach ($this->subTarefas as $sub) {
                $sub->exibir();
            }
        }
        echo str_repeat('-', 50) . PHP_EOL;
    }

    public function resumo(): string
    {
        $icone = $this->prioridade->icone();
        return "{$icone} #{$this->id} [{$this->status->value}] {$this->titulo}";
    }

    // --- Persistivel ---

    public function paraArray(): array
    {
        return [
            'id'          => $this->id,
            'titulo'      => $this->titulo,
            'descricao'   => $this->descricao,
            'prioridade'  => $this->prioridade->value,
            'status'      => $this->status->value,
            'dataCriacao' => $this->dataCriacao,
            'usuarioId'   => $this->usuarioId,
            'subTarefas'  => array_map(fn(SubTarefa $s) => $s->paraArray(), $this->subTarefas),
        ];
    }

    public static function deArray(array $dados): static
    {
        $tarefa = new self(
            id: $dados['id'],
            titulo: $dados['titulo'],
            descricao: $dados['descricao'],
            prioridade: Prioridade::from($dados['prioridade']),
            status: StatusTarefa::from($dados['status']),
            dataCriacao: $dados['dataCriacao'],
        );
        $tarefa->usuarioId = $dados['usuarioId'];
        $tarefa->subTarefas = array_map(
            fn(array $s) => SubTarefa::deArray($s),
            $dados['subTarefas'] ?? [],
        );
        if ($dados['id'] >= self::$contador) {
            self::$contador = $dados['id'] + 1;
        }
        return $tarefa;
    }
}
