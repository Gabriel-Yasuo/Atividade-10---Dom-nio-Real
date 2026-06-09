<?php

declare(strict_types=1);

namespace TaskMaster\Interfaces;

/**
 * Contrato para entidades que podem ser exibidas no terminal.
 * Garante polimorfismo na exibição de diferentes objetos.
 */
interface Exibivel
{
    public function exibir(): void;
    public function resumo(): string;
}
