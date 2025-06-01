<?php

namespace App\Traits;

use App\Models\Coin;
use App\Models\Commerce;
use App\Models\CommerceCountriesCoins;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\Customer;
use App\Models\PagoMovil;
use App\Models\Pix;
use App\Models\Seller;
use App\Models\SellerCountriesCoins;
use App\Models\Treasurer;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait UserVal
{
    public function user(array $fields = ['*'])
    {
        $userId = Auth::id();

        if (!$userId) return null;

        return User::select($fields)->find($userId);
    }

    public function wallet($userID = null)
    {
        return Wallet::with('Coin')->where('user', $userID ?? Auth::id())->first();
    }

    public function commerce($fields = ['*'])
    {
        return Commerce::select($fields)->where('user', Auth::id())->first();
    }

    public function customer($fields = ['*'])
    {
        return Customer::select($fields)->where('user', Auth::id())->first();
    }

    public function treasurer($fields = ['*'])
    {
        return Treasurer::select($fields)->where('user', Auth::id())->first();
    }

    public function seller($fields = ['*'])
    {
        return Seller::select($fields)->where('user', Auth::id())->first();
    }

    public function getAvailableCountries($user)
    {
        $creatorTypeID = User::getValueById($user, 'user_type');
        $creatorType = UserType::getValueById($creatorTypeID, 'prefix');

        $countries = [];

        switch ($creatorType) {
            case 'user': // Jugador
                $countries = [User::getValueById($user, 'country')];
                break;
            case 'admin': // Admin
                # code...
                break;
            case 'commerce': // Comercio
                $commerce = Commerce::getValueByUserId($user, 'id');
                $countries = CommerceCountriesCoins::getAvailableCountries($commerce);
                break;
            case 'seller': // Vendedor
                $seller = Seller::getValueByUserId($user, 'id');
                $countries = SellerCountriesCoins::getAvailableCountries($seller);
                break;
            case 'customer': // Cliente
                //$customer = Customer::getValueByUserId($user, 'id');
                //$countries = CustomerExchangeRate::getAvailableCountries($customer) ?? [];
                break;
            default:
                break;
        }

        return $countries;
    }

    public function getAvailableCoins($user)
    {
        $creatorType = UserType::getValueById(User::getValueById($user, 'user_type'), 'prefix');

        switch ($creatorType) {
            case 'user': // Jugador
                $coins = [User::getValueById($user, 'country')];
                break;
            case 'admin': // Admin
                # code...
                break;
            case 'commerce': // Comercio
                $commerce = Commerce::getValueByUserId($user, 'id');
                $coins = CommerceCountriesCoins::getAvailableCoins($commerce);
                break;
            case 'seller': // Vendedor
                $seller = Seller::getValueByUserId($user, 'id');
                $coins = SellerCountriesCoins::getAvailableCoins($seller);
                break;
            case 'customer': // Cliente
                $seller = Seller::getValueByUserId($user, 'id');
                $coins = SellerCountriesCoins::getAvailableCoins($seller);
                break;
            default:
                $coins = [];
                break;
        }

        return $coins;
    }

    public function checkBankDirectoriesExistence($coins)
    {
        $user = User::getCreatorUser(Auth::id(), false, true);
        $coinModels = Coin::getCoinsById($coins, ['id', 'iso_code']);

        $coinsWithoutDirectories = collect($coinModels)->filter(function ($coin) use ($user) {
            return match ($coin->iso_code) {
                'VES' => !PagoMovil::where('user', $user['userId'])->exists(),
                'BRL' => !Pix::where('user', $user['userId'])->exists(),
                'COP' => true,
                default => throw new \InvalidArgumentException("Tipo de moneda no soportado: {$coin->iso_code}"),
            };
        })->pluck('id')->all();

        if (!in_array($user['type'], ['commerce', 'admin'])) return;

        try {
            DB::beginTransaction();
            $commerceID = Commerce::getValueByUserId($user['userId'], 'id');

            // Obtener los IDs más recientes de cada moneda (coin)
            $ids = CommerceExchangeRate::select(DB::raw('MAX(id) as id'))
                ->where('commerce', $commerceID)
                ->where('status', 'DIRECTORY')
                ->whereNotIn('coin', $coinsWithoutDirectories)
                ->groupBy('coin')
                ->pluck('id');

            // Actualizar en una sola consulta
            CommerceExchangeRate::whereIn('id', $ids)
                ->update(['status' => 'ACTIVE']);

            // Marcar status 'DIRECTORY' en monedas sin directorio
            foreach ($coinsWithoutDirectories as $coinId) {
                $record = CommerceExchangeRate::where('commerce', $commerceID)
                    ->where('coin', $coinId)
                    ->where('status', '!=', 'DIRECTORY')
                    ->orderByDesc('id')
                    ->first();

                if ($record) {
                    $record->update(['status' => 'DIRECTORY']);
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error al actualizar monedas sin directorios: {$th->getMessage()}");
        }
    }

    public function getPaymentDetailsByUserId($user, $coin)
    {
        try {
            $user = User::getById($user, ['id', 'user_type']);

            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }

            if (!in_array($user->user_type, [2, 3, 5])) {
                return response()->json(['error' => 'Tipo de usuario no válido.'], 400);
            }

            $coinInfo = Coin::getById($coin, ['country', 'symbol']);

            if (!$coinInfo) {
                return response()->json(['error' => 'Moneda no válida.'], 404);
            }

            $isoCode = Country::getValue($coinInfo->country, 'iso_code_2');

            if (!$isoCode) {
                return response()->json(['error' => 'Código ISO del país no encontrado.'], 404);
            }

            $user = $user->id;
            $error = false;

            $paymentMethod = [];

            switch ($isoCode) {
                case 'VE':
                    // Bs (Venezuela) PagoMovil
                    $pagomovil = PagoMovil::with(['Bank', 'DocumentType'])->where('user', $user)->first();

                    if (!$pagomovil) $error = true;

                    $paymentMethod = [
                        '0' => ["({$pagomovil->Bank->code}) {$pagomovil->Bank->name}"],
                        '1' => ["{$pagomovil->DocumentType->value}-{$pagomovil->document}"],
                        '2' => [$pagomovil->phone]
                    ];
                    break;

                case 'BR':
                    // R$ (Brasil) Pix
                    $pix = Pix::where('user', $user)->first();

                    if (!$pix) $error = true;

                    $paymentMethod = ['0' => ["{$pix->chave_pix}: {$pix->pix_key_value}"]];
                    break;

                default:
                    return ['error' => 'Tipo de moneda no soportado'];
            }

            if ($error) {
                return ['error' => 'El usuario no ha cargado los datos bancarios'];
            }

            return [
                'paymentMethod' => $paymentMethod,
                'code' => $isoCode,
                'coin' => $coinInfo->symbol
            ];
        } catch (\Exception $e) {
            Log::error('Ha ocurrido un error al procesar la solicitud: ' . $e->getMessage());
            return response()->json(['error' => 'Ha ocurrido un error al procesar la solicitud.'], 500);
        }
    }

    public function getUserCoins($user, $relation = [], $dateFilter = 'today', $where = null)
    {
        $userType = $user['type'];
        $userId = $userType !== 'seller' ? $user['userId'] : $user['id'];
        $userColumn = $userType !== 'seller' ? 'creator_user' : 'seller';

        $primaryRelation = $relation[0] ?? null;
        $secondaryRelation = $relation[1] ?? null;

        if (!$primaryRelation) {
            return collect(); // No hay relación principal
        }

        // Construcción de la consulta principal
        $query = CommerceCountriesCoins::select('id', 'coin')
            ->with($primaryRelation)
            ->whereHas($primaryRelation, function ($subquery) use (
                $userId,
                $userColumn,
                $dateFilter,
                $primaryRelation,
                $secondaryRelation,
                $where
            ) {
                // Condiciones base
                $subquery->whereColumn('coin', 'commerces_countries_coins.coin')
                    ->where($userColumn, $userId);

                // Filtro por fecha
                $this->applyDateFilter($dateFilter, $subquery);

                // Si hay relación secundaria, incluirla
                if ($secondaryRelation) {
                    $subquery->with($secondaryRelation);
                }

                // Fusionar condiciones si son para la misma relación primaria
                if ($where && $where->isNotEmpty()) {
                    foreach ($where as $item) {
                        if (isset($item->relation, $item->constraints) && $item->relation === $primaryRelation) {
                            call_user_func($item->constraints, $subquery);
                        }
                    }
                }
            });

        // Agregar condiciones adicionales (solo las que NO son para la relación primaria)
        if ($where && $where->isNotEmpty()) {
            foreach ($where as $item) {
                if (isset($item->relation, $item->constraints) && $item->relation !== $primaryRelation) {
                    $query->whereHas($item->relation, $item->constraints);
                } elseif (isset($item->column, $item->value)) {
                    $query->where($item->column, $item->value);
                }
            }
        }

        $coinIDs = $query->where('status', 1)->orderBy('country')->distinct()->pluck('coin');

        return Coin::getCoinsById($coinIDs);
    }

    public function getUserData()
    {
        $user = $this->user(['id', 'user_type']);
        $userId = $user->id;

        $userType = UserType::getValueById($user->user_type, 'prefix');

        switch ($userType) {
            case 'commerce':
                $commerce = $this->commerce(['id', 'user']);
                $userTypeId = $commerce->id;
                break;
            case 'customer':
                $customer = $this->customer(['id', 'user']);
                $userTypeId = $customer->id;
                break;
            case 'seller':
                $seller = $this->seller(['id', 'user']);
                $userTypeId = $seller->id;
                break;
            default:
                # code...
                break;
        }

        return ['type' => $userType, 'id' => $userTypeId, 'userId' => $userId];
    }

    public function insertOtherCoins($creatorUser, $otherCoins)
    {
        if (empty($otherCoins) || !is_array($otherCoins)) return;

        $creatorID = $creatorUser['id'];
        $modelName = $creatorUser['model'];
        $userColumn = strtolower($modelName);
        $model = app("App\\Models\\{$modelName}CountriesCoins");

        $otherCoins = Coin::getCoinsById($otherCoins, ['id', 'country']);
        $coinsToCreate = [];
        $coinIds = [];
        $timestamp = now();

        foreach ($otherCoins as $coin) {
            $coinIds[] = $coin->id;
            $coinsToCreate[] = [
                $userColumn => $creatorID,
                'country' => $coin->country,
                'coin' => $coin->id,
                'level' => '2',
                'creator_user' => $creatorUser['userId'],
                'status' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
        }

        DB::transaction(function () use ($model, $userColumn, $creatorID, $coinsToCreate, $coinIds) {
            try {
                foreach ($coinsToCreate as $coinData) {
                    $model::firstOrCreate(
                        [$userColumn => $creatorID, 'coin' => $coinData['coin']],
                        $coinData
                    );
                }

                $model::where($userColumn, $creatorID)
                    ->whereIn('coin', $coinIds)
                    ->update(['status' => 1]);

                $model::where($userColumn, $creatorID)
                    ->where('level', '2')->whereNotIn('coin', $coinIds)
                    ->update(['status' => 0]); // monedas no seleccionadas = inactivas

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error al insertar monedas para el $userColumn: " . $e->getMessage());
                throw $e;
            }
        });
    }

    public function handleUserGamePermission($creatorUser, $allowedGames)
    {
        return;
        if (empty($allowedGames) || !is_array($allowedGames)) return;

        $creatorID = $creatorUser['id'];
        $modelName = $creatorUser['model'];
        $userColumn = strtolower($modelName);
        $model = app("App\\Models\\{$modelName}CountriesCoins");

        $allowedGames = Coin::getCoinsById($allowedGames, ['id', 'country']);
        $coinsToCreate = [];
        $coinIds = [];
        $timestamp = now();

        foreach ($allowedGames as $coin) {
            $coinIds[] = $coin->id;
            $coinsToCreate[] = [
                $userColumn => $creatorID,
                'country' => $coin->country,
                'coin' => $coin->id,
                'level' => '2',
                'creator_user' => $creatorUser['userId'],
                'status' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
        }

        DB::transaction(function () use ($model, $userColumn, $creatorID, $coinsToCreate, $coinIds) {
            try {
                foreach ($coinsToCreate as $coinData) {
                    $model::firstOrCreate(
                        [$userColumn => $creatorID, 'coin' => $coinData['coin']],
                        $coinData
                    );
                }

                $model::where($userColumn, $creatorID)
                    ->whereIn('coin', $coinIds)
                    ->update(['status' => 1]);

                $model::where($userColumn, $creatorID)
                    ->where('level', '2')->whereNotIn('coin', $coinIds)
                    ->update(['status' => 0]); // monedas no seleccionadas = inactivas

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error al insertar monedas para el $userColumn: " . $e->getMessage());
                throw $e;
            }
        });
    }
}
