<?php

declare(strict_types=1);

namespace TaskMaster\Interfaces;

/**
 * Contrato para entidades que podem ser salvas e carregadas em disco.
 */
interface Persistivel
{
    public function paraArray(): array;
    public static function deArray(array $dados): static;
}
