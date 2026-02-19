<?php

namespace App\Services\Games\Bingo\Config;

use Illuminate\Http\UploadedFile;

final class BingoConfigService
{
    public function __construct(
        private readonly BingoConfigContextService $contextService,
    ) {}

    /**
     * Resolver contexto para BingoConfig.
     *
     * @param string   $context
     * @param int|null $userId
     * @return array
     */
    public function context(string $context, ?int $userId = null): array
    {
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Guarda o actualiza las reglas del bingo para un usuario.
     *
     * @param int               $userId
     * @param string            $rules
     * @param UploadedFile|null $image
     */
    public function saveRules(int $userId, string $rules, ?UploadedFile $image): void
    {
        $this->contextService->saveRules($userId, $rules, $image);
    }
}
