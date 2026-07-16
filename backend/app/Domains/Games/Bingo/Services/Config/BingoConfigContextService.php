<?php

namespace App\Domains\Games\Bingo\Services\Config;

use App\Domains\Games\Bingo\DTOs\Config\BingoConfigDTO;
use App\Contracts\Games\Common\Utils\GameRuleRepositoryInterface;
use App\Models\Game;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class BingoConfigContextService
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
     * Guarda solo la imagen del reglamento y retorna el path público relativo.
     */
    public function saveOnlyImage(int $userId, UploadedFile $image): string
    {
        $gameId    = $this->resolveBingoGameId();
        $imagePath = '';

        DB::transaction(function () use ($userId, $gameId, $image, &$imagePath) {
            $existing = $this->gameRule->getByUserAndGame($userId, $gameId);

            if ($existing) {
                $filename = $this->storeImage($image, $existing->encrypted);
                $existing->update(['image' => $filename]);
                $imagePath = "bingo-rules/{$existing->encrypted}/{$filename}";
            } else {
                $encrypted = $this->gameRule->generateUniqueEncrypted();
                $filename  = $this->storeImage($image, $encrypted);
                $this->gameRule->createRule(
                    userId:    $userId,
                    gameId:    $gameId,
                    rules:     '',
                    encrypted: $encrypted,
                    image:     $filename,
                );
                $imagePath = "bingo-rules/{$encrypted}/{$filename}";
            }
        });

        return $imagePath;
    }

    /**
     * Retorna el path relativo de la imagen de reglas del bingo para un usuario.
     */
    public function getImageBingoRules(int $userId): string
    {
        $gameId = $this->bingoGameId();
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
        $gameId = $this->bingoGameId();
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
        $gameId = $this->bingoGameId();

        if (!$gameId) {
            throw new DomainException('El juego BINGO no existe en la base de datos.');
        }

        return $gameId;
    }

    /**
     * ID del juego BINGO, cacheado indefinidamente: fila de catálogo estática
     * que nunca cambia en runtime. Usar forgetBingoGameIdCache() si el seeder
     * se re-ejecuta en un entorno existente.
     */
    private function bingoGameId(): int|string|null
    {
        return Cache::rememberForever('game:id:BINGO', fn () => Game::where('name', 'BINGO')->value('id'));
    }

    public static function forgetBingoGameIdCache(): void
    {
        Cache::forget('game:id:BINGO');
    }

    /**
     * Guarda la imagen en storage y retorna el nombre del archivo.
     */
    private function storeImage(UploadedFile $file, string $encrypted): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $filename  = Str::random(16) . '.' . $extension;
        $path      = "game/bingo/bingo-rules/{$encrypted}/{$filename}";

        Storage::disk('public')->put($path, $file->getContent());

        return $filename;
    }
}
