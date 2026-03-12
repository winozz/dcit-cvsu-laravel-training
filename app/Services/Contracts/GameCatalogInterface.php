<?php

namespace App\Services\Contracts;

interface GameCatalogInterface
{
    /** @return array<int,array> */
    public function all(bool $includeDefaults = true, bool $guest = false): array;

    /**
     * @param array{name?:string,slug?:string|null,description?:string|null} $data
     * @return array{ok:bool,message?:string,game?:array}
     */
    public function add(array $data, bool $guest = false): array;

    public function reset(bool $guest = false): void;
}
