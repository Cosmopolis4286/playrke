<?php

namespace App\Services\Games\Bingo\Config;

use App\DTOs\Games\Bingo\Config\BingoConfigDTO;
use App\Contracts\Games\Common\Utils\GameRuleRepositoryInterface;
use App\Traits\HashedTableDataTrait;
use DomainException;

final class BingoConfigContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly GameRuleRepositoryInterface $gameRule,
    ) {}

    /**
     * Resolver contexto para BingoRoom.
     *
     * @param string $context
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
     * Resolver contexto para la configuracio del jugego bingo.
     *
     * @param int $userId ID del usuario
     * @return BingoConfigDTO
     *
     * @throws DomainException Si no se puede resolver el contexto
     */
    private function resolve(int $userId): BingoConfigDTO
    {
        return new BingoConfigDTO(
            imageBingoRules: $this->getImageBingoRules($userId),
            bingoRules: $this->getBingoRules()
        );
    }

    private function getImageBingoRules($userId): string
    {
        $game = $this->gameRule->getByGame('BINGO', $userId, ['image', 'encrypted']);
        $image = false;

        if ($game) {
            $image = "bingo-rules/$game->encrypted/$game->image";
        }

        return $image;
    }

    public function getBingoRules($userId): string
    {
        return $this->gameRule->getValueByGame('BINGO', $userId);
    }
}
