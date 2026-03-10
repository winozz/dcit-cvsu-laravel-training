<?php

namespace App\Services\Contracts;

interface GameCatalogContract
{
    /** @return array<int,array> */
    public function all(): array;

    /**
     * @param array{name?:string,slug?:string|null,description?:string|null} $data
     * @return array{ok:bool,message?:string,game?:array}
     */
    public function add(array $data): array;

    public function reset(): void;
}
