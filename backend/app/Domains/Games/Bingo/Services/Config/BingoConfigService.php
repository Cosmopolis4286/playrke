<?php

namespace App\Domains\Games\Bingo\Services\Config;

use Illuminate\Http\UploadedFile;

final class BingoConfigService
{
    public function __construct(
        private readonly BingoConfigContextService $contextService,
    ) {}

    public function context(string $context, ?int $userId = null): array
    {
        return $this->contextService->context($context, $userId)->toView();
    }

    /**
     * Guarda solo el texto del reglamento.
     */
    public function saveRules(int $userId, string $rules): void
    {
        $this->contextService->saveOnlyRules($userId, $rules);
    }

    /**
     * Guarda solo la imagen del reglamento y retorna el path público relativo.
     */
    public function saveImage(int $userId, UploadedFile $image): string
    {
        return $this->contextService->saveOnlyImage($userId, $image);
    }
}
