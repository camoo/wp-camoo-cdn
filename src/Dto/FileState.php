<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Dto;

final class FileState
{
    public function __construct(public string $path, public int $mtime, public string $state)
    {
    }
}
