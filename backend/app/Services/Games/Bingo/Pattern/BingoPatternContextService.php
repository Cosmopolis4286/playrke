<?php

namespace App\Services\Games\Bingo\Pattern;

use App\DTOs\Games\Bingo\Config\BingoConfigDTO;
use App\Contracts\Games\Common\Utils\GameRuleRepositoryInterface;
use App\Models\Game;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class BingoPatternContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly GameRuleRepositoryInterface $gameRule,
    ) {}

    /**
     * Resolver contexto para BingoConfig.
     */
    public function context(string $context, ?int $userId): mixed
    {
        return match ($context) {
            'index' => $this->resolve($userId),
            default => throw new DomainException('No existe el contexto: ' . $context),
        };
    }

    private function resolve(int $userId): BingoConfigDTO
    {
        return new BingoConfigDTO(
            imageBingoRules: $this->getImageBingoRules($userId),
            bingoRules:      $this->getBingoRules($userId),
        );
    }

    /**
     * Guarda solo el texto del reglamento.
     */
    public function saveOnlyRules(int $userId, string $rules): void
    {
        $gameId = $this->resolveBingoGameId();

        DB::transaction(function () use ($userId, $gameId, $rules) {
            $existing = $this->gameRule->getByUserAndGame($userId, $gameId);

            if ($existing) {
                if ($existing->rules !== $rules) {
                    $existing->update(['rules' => $rules]);
                }
            } else {
                $this->gameRule->createRule(
                    userId:    $userId,
                    gameId:    $gameId,
                    rules:     $rules,
                    encrypted: $this->gameRule->generateUniqueEncrypted(),
                );
            }
        });
    }

    /**
     * Guarda solo la imagen del reglamento.
     */
    public function saveOnlyImage(int $userId, UploadedFile $image): void
    {
        $gameId = $this->resolveBingoGameId();

        DB::transaction(function () use ($userId, $gameId, $image) {
            $existing = $this->gameRule->getByUserAndGame($userId, $gameId);

            if ($existing) {
                $existing->update([
                    'image' => $this->storeImage($image, $existing->encrypted),
                ]);
            } else {
                $encrypted = $this->gameRule->generateUniqueEncrypted();
                $this->gameRule->createRule(
                    userId:    $userId,
                    gameId:    $gameId,
                    rules:     '',
                    encrypted: $encrypted,
                    image:     $this->storeImage($image, $encrypted),
                );
            }
        });
    }

    /**
     * Retorna el path relativo de la imagen de reglas del bingo para un usuario.
     */
    private function getImageBingoRules(int $userId): string
    {
        $gameId = Game::where('name', 'BINGO')->value('id');
        $game   = $gameId ? $this->gameRule->getByUserAndGame($userId, $gameId) : null;

        if ($game && $game->image && $game->encrypted) {
            return "bingo-rules/{$game->encrypted}/{$game->image}";
        }

        return '';
    }

    /**
     * Retorna el texto de las reglas del bingo para un usuario.
     */
    public function getBingoRules(int $userId): string
    {
        $gameId = Game::where('name', 'BINGO')->value('id');
        $game   = $gameId ? $this->gameRule->getByUserAndGame($userId, $gameId) : null;

        return $game?->rules ?? '';
    }

    /**
     * Resuelve el ID del juego BINGO.
     *
     * @throws DomainException
     */
    private function resolveBingoGameId(): int|string
    {
        $gameId = Game::where('name', 'BINGO')->value('id');

        if (!$gameId) {
            throw new DomainException('El juego BINGO no existe en la base de datos.');
        }

        return $gameId;
    }

    /**
     * Guarda la imagen en storage y retorna el nombre del archivo.
     */
    private function storeImage(UploadedFile $file, string $encrypted): string
    {
        $path = $file->store("game/bingo/bingo-rules/{$encrypted}", 'public');

        return basename($path);
    }
}
