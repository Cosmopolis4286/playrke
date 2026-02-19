<?php

namespace App\Services\Games\Bingo\Config;

use App\DTOs\Games\Bingo\Config\BingoConfigDTO;
use App\Contracts\Games\Common\Utils\GameRuleRepositoryInterface;
use App\Models\Game;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class BingoConfigContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly GameRuleRepositoryInterface $gameRule,
    ) {}

    /**
     * Resolver contexto para BingoConfig.
     *
     * @param string   $context
     * @param int|null $userId
     * @return mixed
     * @throws DomainException Si el contexto no es válido
     */
    public function context(string $context, ?int $userId): mixed
    {
        return match ($context) {
            'index' => $this->resolve($userId),
            default => throw new DomainException('No existe el contexto: ' . $context),
        };
    }

    /**
     * Resuelve el contexto de index para la configuración del bingo.
     */
    private function resolve(int $userId): BingoConfigDTO
    {
        return new BingoConfigDTO(
            imageBingoRules: $this->getImageBingoRules($userId),
            bingoRules:      $this->getBingoRules($userId),
        );
    }

    /**
     * Guarda o actualiza las reglas de bingo (imagen + texto) para un usuario.
     *
     * @param int               $userId
     * @param string            $rules
     * @param UploadedFile|null $image
     * @throws DomainException
     */
    public function saveRules(int $userId, string $rules, ?UploadedFile $image): void
    {
        $gameId = Game::where('name', 'BINGO')->value('id');

        if (!$gameId) {
            throw new DomainException('El juego BINGO no existe en la base de datos.');
        }

        DB::transaction(function () use ($userId, $gameId, $rules, $image) {
            $existing = $this->gameRule->getByUserAndGame($userId, $gameId);

            if ($existing) {
                $updateData = [];

                if ($existing->rules !== $rules) {
                    $updateData['rules'] = $rules;
                }

                if ($image) {
                    $updateData['image'] = $this->storeImage($image, $existing->encrypted);
                }

                if (!empty($updateData)) {
                    $existing->update($updateData);
                }
            } else {
                $encrypted = $this->gameRule->generateUniqueEncrypted();
                $imageName = $image ? $this->storeImage($image, $encrypted) : null;

                $this->gameRule->createRule(
                    userId:    $userId,
                    gameId:    $gameId,
                    rules:     $rules,
                    encrypted: $encrypted,
                    image:     $imageName,
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
     * Guarda la imagen en storage y retorna el nombre del archivo.
     */
    private function storeImage(UploadedFile $file, string $encrypted): string
    {
        $path = $file->store("game/bingo/bingo-rules/{$encrypted}", 'public');

        return basename($path);
    }
}
