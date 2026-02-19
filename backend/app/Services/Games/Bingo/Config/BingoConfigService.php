<?php

namespace App\Services\Games\Bingo\Config;

final class BingoConfigService
{
    public function __construct(
        private readonly BingoConfigContextService $contextService,
    ) {}

    /**
     * Resolver contexto para BingoConfig.
     *
     * @param string $context
     * @param int|null $userId
     * @return array
     * @throws \BingoConfigException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId = null): array
    {
        return $this->contextService->context($context, $userId)->toView();
    }
}
