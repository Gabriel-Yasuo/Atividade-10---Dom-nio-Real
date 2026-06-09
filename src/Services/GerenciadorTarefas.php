<?php

declare(strict_types=1);

namespace TaskMaster\Services;

use TaskMaster\Models\{Tarefa, SubTarefa, Usuario, Categoria, Prioridade, StatusTarefa};

/**
 * GerenciadorTarefas — serviço central que orquestra o sistema.
 * Mantém as coleções de Tarefa, Usuario e Categoria em memória
 * e persiste tudo em um arquivo JSON.
 */
class GerenciadorTarefas
{
    /** @var Tarefa[] */
    private array $tarefas = [];

    /** @var Usuario[] */
    private array $usuarios = [];

    /** @var Categoria[] */
    private array $categorias = [];

    public function __construct(
        private readonly string $arquivoDados,
    ) {
        $this->carregar();
    }

    // =========================================================
    // TAREFAS
    // =========================================================

    public function criarTarefa(string $titulo, string $descricao, string $prioridade = 'média'): Tarefa
    {
        $p = Prioridade::from($prioridade);
        $tarefa = Tarefa::criar($titulo, $descricao, $p);
        $this->tarefas[$tarefa->getId()] = $tarefa;
        $this->salvar();
        return $tarefa;
    }

    public function listarTarefas(?string $filtroStatus = null): void
    {
        if (empty($this->tarefas)) {
            echo "  Nenhuma tarefa cadastrada." . PHP_EOL;
            return;
        }

        $lista = $this->tarefas;
        if ($filtroStatus !== null) {
            $lista = array_filter(
                $lista,
                fn(Tarefa $t) => $t->getStatus()->value === $filtroStatus
            );
        }

        if (empty($lista)) {
            echo "  Nenhuma tarefa com status '{$filtroStatus}'." . PHP_EOL;
            return;
        }

        // Polimorfismo: chama exibir() em Tarefa — mesma interface que Categoria e Usuario
        foreach ($lista as $tarefa) {
            $tarefa->exibir();
        }
    }

    public function verTarefa(int $id): void
    {
        $tarefa = $this->buscarTarefa($id);
        if ($tarefa === null) {
            echo "  Tarefa #{$id} não encontrada." . PHP_EOL;
            return;
        }
        $tarefa->exibir();
    }

    public function atualizarStatusTarefa(int $id, string $novoStatus): bool
    {
        $tarefa = $this->buscarTarefa($id);
        if ($tarefa === null) {
            return false;
        }
        $tarefa->atualizarStatus(StatusTarefa::from($novoStatus));
        $this->salvar();
        return true;
    }

    public function removerTarefa(int $id): bool
    {
        if (!isset($this->tarefas[$id])) {
            return false;
        }
        // Remove referência em categorias (agregação: categoria não é destruída)
        foreach ($this->categorias as $categoria) {
            $categoria->removerTarefa($id);
        }
        unset($this->tarefas[$id]);
        $this->salvar();
        return true;
    }

    // =========================================================
    // SUB-TAREFAS (composição — criadas dentro da tarefa)
    // =========================================================

    public function adicionarSubTarefa(int $tarefaId, string $descricao): ?SubTarefa
    {
        $tarefa = $this->buscarTarefa($tarefaId);
        if ($tarefa === null) {
            return null;
        }
        $sub = $tarefa->adicionarSubTarefa($descricao);
        $this->salvar();
        return $sub;
    }

    public function concluirSubTarefa(int $tarefaId, int $subId): bool
    {
        $tarefa = $this->buscarTarefa($tarefaId);
        if ($tarefa === null) {
            return false;
        }
        $resultado = $tarefa->concluirSubTarefa($subId);
        if ($resultado) {
            $this->salvar();
        }
        return $resultado;
    }

    // =========================================================
    // USUÁRIOS (associação com Tarefa)
    // =========================================================

    public function criarUsuario(string $nome, string $email): Usuario
    {
        $usuario = Usuario::criar($nome, $email);
        $this->usuarios[$usuario->getId()] = $usuario;
        $this->salvar();
        return $usuario;
    }

    public function listarUsuarios(): void
    {
        if (empty($this->usuarios)) {
            echo "  Nenhum usuário cadastrado." . PHP_EOL;
            return;
        }
        // Polimorfismo: mesma interface Exibivel
        foreach ($this->usuarios as $usuario) {
            $usuario->exibir();
        }
    }

    public function atribuirTarefa(int $tarefaId, int $usuarioId): bool
    {
        $tarefa  = $this->buscarTarefa($tarefaId);
        $usuario = $this->buscarUsuario($usuarioId);

        if ($tarefa === null || $usuario === null) {
            return false;
        }

        // Associação: tarefa guarda id do usuario, usuario guarda id da tarefa
        $tarefa->atribuirUsuario($usuarioId);
        $usuario->adicionarTarefaId($tarefaId);
        $this->salvar();
        return true;
    }

    // =========================================================
    // CATEGORIAS (agregação com Tarefa)
    // =========================================================

    public function criarCategoria(string $nome, string $cor = 'branco'): Categoria
    {
        $categoria = Categoria::criar($nome, $cor);
        $this->categorias[$categoria->getId()] = $categoria;
        $this->salvar();
        return $categoria;
    }

    public function listarCategorias(): void
    {
        if (empty($this->categorias)) {
            echo "  Nenhuma categoria cadastrada." . PHP_EOL;
            return;
        }
        // Polimorfismo: mesma interface Exibivel
        foreach ($this->categorias as $categoria) {
            $categoria->exibir();
        }
    }

    public function categorizarTarefa(int $tarefaId, int $categoriaId): bool
    {
        $tarefa    = $this->buscarTarefa($tarefaId);
        $categoria = $this->buscarCategoria($categoriaId);

        if ($tarefa === null || $categoria === null) {
            return false;
        }

        // Agregação: categoria apenas referencia a tarefa; tarefa segue existindo sozinha
        $categoria->adicionarTarefa($tarefa);
        $this->salvar();
        return true;
    }

    public function listarTarefasPorCategoria(int $categoriaId): void
    {
        $categoria = $this->buscarCategoria($categoriaId);
        if ($categoria === null) {
            echo "  Categoria não encontrada." . PHP_EOL;
            return;
        }

        echo "  Categoria: {$categoria->getNome()}" . PHP_EOL;
        $ids = $categoria->getTarefasIds();
        if (empty($ids)) {
            echo "  Nenhuma tarefa nesta categoria." . PHP_EOL;
            return;
        }

        foreach ($ids as $id) {
            $tarefa = $this->buscarTarefa($id);
            if ($tarefa !== null) {
                echo "  " . $tarefa->resumo() . PHP_EOL;
            }
        }
    }

    // =========================================================
    // RELATÓRIO GERAL
    // =========================================================

    public function relatorio(): void
    {
        $total     = count($this->tarefas);
        $concluidas = count(array_filter(
            $this->tarefas,
            fn(Tarefa $t) => $t->getStatus() === StatusTarefa::Concluida
        ));
        $pendentes  = count(array_filter(
            $this->tarefas,
            fn(Tarefa $t) => $t->getStatus() === StatusTarefa::Pendente
        ));
        $urgentes   = count(array_filter(
            $this->tarefas,
            fn(Tarefa $t) => $t->getPrioridade() === Prioridade::Urgente
        ));

        echo PHP_EOL;
        echo "  ╔══════════════════════════════╗" . PHP_EOL;
        echo "  ║       RELATÓRIO GERAL        ║" . PHP_EOL;
        echo "  ╠══════════════════════════════╣" . PHP_EOL;
        echo "  ║  Total de tarefas : {$this->pad($total)}" . PHP_EOL;
        echo "  ║  Concluídas       : {$this->pad($concluidas)}" . PHP_EOL;
        echo "  ║  Pendentes        : {$this->pad($pendentes)}" . PHP_EOL;
        echo "  ║  Urgentes         : {$this->pad($urgentes)}" . PHP_EOL;
        echo "  ║  Usuários         : {$this->pad(count($this->usuarios))}" . PHP_EOL;
        echo "  ║  Categorias       : {$this->pad(count($this->categorias))}" . PHP_EOL;
        echo "  ╚══════════════════════════════╝" . PHP_EOL;
    }

    // =========================================================
    // PERSISTÊNCIA (JSON)
    // =========================================================

    private function salvar(): void
    {
        $dados = [
            'tarefas'    => array_map(fn(Tarefa $t) => $t->paraArray(), $this->tarefas),
            'usuarios'   => array_map(fn(Usuario $u) => $u->paraArray(), $this->usuarios),
            'categorias' => array_map(fn(Categoria $c) => $c->paraArray(), $this->categorias),
        ];
        file_put_contents($this->arquivoDados, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function carregar(): void
    {
        if (!file_exists($this->arquivoDados)) {
            return;
        }

        $json  = file_get_contents($this->arquivoDados);
        $dados = json_decode($json, true);

        if (!is_array($dados)) {
            return;
        }

        foreach ($dados['tarefas'] ?? [] as $t) {
            $tarefa = Tarefa::deArray($t);
            $this->tarefas[$tarefa->getId()] = $tarefa;
        }
        foreach ($dados['usuarios'] ?? [] as $u) {
            $usuario = Usuario::deArray($u);
            $this->usuarios[$usuario->getId()] = $usuario;
        }
        foreach ($dados['categorias'] ?? [] as $c) {
            $categoria = Categoria::deArray($c);
            $this->categorias[$categoria->getId()] = $categoria;
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function buscarTarefa(int $id): ?Tarefa
    {
        return $this->tarefas[$id] ?? null;
    }

    private function buscarUsuario(int $id): ?Usuario
    {
        return $this->usuarios[$id] ?? null;
    }

    private function buscarCategoria(int $id): ?Categoria
    {
        return $this->categorias[$id] ?? null;
    }

    private function pad(int $valor): string
    {
        return str_pad((string)$valor, 10) . '║';
    }
}
