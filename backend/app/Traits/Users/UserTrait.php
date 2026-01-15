<?php

namespace App\Traits\Users;

use App\Models\Country;
use App\Models\Currency;
use App\Models\ExchangeRate\CommerceExchangeRate;
use App\Models\Directories\Fiat\Brazil\PixKey;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Models\Roles;
use App\Models\RolesCurrency;
use App\Models\Roles\Customer\Customer;
use App\Models\Roles\Seller;
use App\Models\Roles\SellerCurrency;
use App\Models\Roles\Treasurer\Treasurer;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\User\Roles\RoleRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait UserTrait
{
    public function user(array $fields = ['*'])
    {
        $userId = Auth::id();

        if (!$userId) return null;

        return User::select($fields)->find($userId);
    }

    public function wallet($userId = null)
    {
        return Wallet::with('Currency')->where('user_id', $userId ?? Auth::id())->first();
    }

    public function commerce($fields = ['*'])
    {
        return Commerce::select($fields)->where('user_id', Auth::id())->first();
    }

    public function customer($fields = ['*'])
    {
        return Customer::select($fields)->where('user_id', Auth::id())->first();
    }

    public function treasurer($fields = ['*'])
    {
        return Treasurer::select($fields)->where('user_id', Auth::id())->first();
    }

    public function seller($fields = ['*'])
    {
        return Seller::select($fields)->where('user_id', Auth::id())->first();
    }

    public function getAvailableCountries($user)
    {
        $creatorTypeID = User::getValueById($user, 'user_type');
        $creatorType = RoleRepository::getValueById($creatorTypeID, 'prefix');

        $countries = [];

        switch ($creatorType) {
            case 'user_id': // Jugador
                $countries = [User::getValueById($user, 'country')];
                break;
            case 'admin': // Admin
                # code...
                break;
            case 'commerce': // Comercio
                $commerce = Commerce::getValueByUserId($user, 'id');
                $countries = CommerceCurrency::getAvailableCountries($commerce);
                break;
            case 'seller': // Vendedor
                $seller = Seller::getValueByUserId($user, 'id');
                $countries = SellerCurrency::getAvailableCountries($seller);
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

    public function getAvailableCurrencies($user)
    {
        $creatorType = RoleRepository::getValueById(User::getValueById($user, 'user_type'), 'prefix');

        switch ($creatorType) {
            case 'user_id': // Jugador
                $currencies = [User::getValueById($user, 'country')];
                break;
            case 'admin': // Admin
                # code...
                break;
            case 'commerce_id': // Comercio
                $commerce = Commerce::getValueByUserId($user, 'id');
                $currencies = CommerceCurrency::getAvailableCurrencies($commerce);
                break;
            case 'seller': // Vendedor
                $seller = Seller::getValueByUserId($user, 'id');
                $currencies = SellerCurrency::getAvailableCurrencies($seller);
                break;
            case 'customer': // Cliente
                $seller = Seller::getValueByUserId($user, 'id');
                $currencies = SellerCurrency::getAvailableCurrencies($seller);
                break;
            default:
                $currencies = [];
                break;
        }

        return $currencies;
    }

    public function checkBankDirectoriesExistence($currencies)
    {
        $user = User::getCreatorUser(Auth::id(), false, true);
        $currencyModels = Currency::getCurrenciesById($currencies, ['id', 'iso_code']);

        $currenciesWithoutDirectories = collect($currencyModels)->filter(function ($currency) use ($user) {
            return match ($currency->iso_code) {
                'VES' => !PagoMovil::where('user_id', $user['userId'])->exists(),
                'BRL' => !PixKey::where('user_id', $user['userId'])->exists(),
                'COP' => true,
                default => throw new \InvalidArgumentException("Tipo de moneda no soportado: {$currency->iso_code}"),
            };
        })->pluck('id')->all();

        if (!in_array($user['type'], ['commerce', 'admin'])) return;

        DB::beginTransaction();

        try {

            $commerceId = Commerce::getValueByUserId($user['userId'], 'id');

            // Obtener los IDs más recientes de cada moneda (currency)
            $ids = CommerceExchangeRate::select(DB::raw('MAX(id) as id'))
                ->where('commerce_id', $commerceId)
                ->whereNotIn('currency', $currenciesWithoutDirectories)
                ->groupBy('currency')
                ->pluck('id');

            CommerceExchangeRate::where('commerce_id', $commerceId)
                ->whereIn('id', $ids)->where('exchange_rate', 0)
                ->whereIn('status', ['ACTIVE', 'DIRECTORY'])
                ->update(['status' => 'INACTIVE']);

            if ($currenciesWithoutDirectories) {
                // Marcar status 'DIRECTORY' en monedas sin directorio
                CommerceExchangeRate::where('commerce_id', $commerceId)
                    ->whereIn('id', $ids)->where('exchange_rate', '>', 0)
                    ->whereIn('currency', $currenciesWithoutDirectories)
                    ->where('status', '!=', 'DIRECTORY')
                    ->update(['status' => 'DIRECTORY']);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error al actualizar monedas sin directorios: {$th->getMessage()}");
        }
    }

    public function getPaymentDetailsByUserId($user, $currency)
    {
        try {
            $user = User::getById($user, ['id', 'user_type']);

            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }

            if (!in_array($user->user_type, [2, 3, 5])) {
                return response()->json(['error' => 'Tipo de usuario no válido.'], 400);
            }

            $currencyInfo = Currency::getById($currency, ['country', 'symbol']);

            if (!$currencyInfo) {
                return response()->json(['error' => 'Moneda no válida.'], 404);
            }

            $isoCode = Country::getValue($currencyInfo->country, 'iso_code_2');

            if (!$isoCode) {
                return response()->json(['error' => 'Código ISO del país no encontrado.'], 404);
            }

            $user = $user->id;
            $error = false;

            $paymentMethod = [];

            switch ($isoCode) {
                case 'VE':
                    // Bs (Venezuela) PagoMovil
                    $pagomovil = PagoMovil::with(['Bank', 'DocumentType'])->where('user_id', $user)->first();

                    if (!$pagomovil) $error = true;

                    $paymentMethod = [
                        '0' => ["({$pagomovil->Bank->code}) {$pagomovil->Bank->name}"],
                        '1' => ["{$pagomovil->DocumentType->value}-{$pagomovil->document}"],
                        '2' => [$pagomovil->phone]
                    ];
                    break;

                case 'BR':
                    // R$ (Brasil) Pix
                    $pix = PixKey::where('user_id', $user)->first();

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
                'currency' => $currencyInfo->symbol
            ];
        } catch (\Exception $e) {
            Log::error('Ha ocurrido un error al procesar la solicitud: ' . $e->getMessage());
            return response()->json(['error' => 'Ha ocurrido un error al procesar la solicitud.'], 500);
        }
    }

    public function getUserCurrencies($user, $relation = [], $dateFilter = 'today', $where = null)
    {
        $userRole = $user['type'];
        $userId = $userRole !== 'seller' ? $user['userId'] : $user['id'];
        $userColumn = $userRole !== 'seller' ? 'creator_user_id' : 'seller';

        $primaryRelation = $relation[0] ?? null;
        $secondaryRelation = $relation[1] ?? null;

        if (!$primaryRelation) {
            return collect(); // No hay relación principal
        }

        // Construcción de la consulta principal
        $query = CommerceCurrency::select('id', 'currency')
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
                $subquery->whereColumn('currency', 'commerces_currencies.currency')
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

        $currencyIds = $query->where('status', 1)->orderBy('country')->distinct()->pluck('currency');

        return Currency::getCurrenciesById($currencyIds);
    }

    public function getUserData()
    {
        $user = $this->user(['id', 'user_type']);
        $userId = $user->id;

        $userRole = RoleRepository::getValueById($user->user_type, 'prefix');

        switch ($userRole) {
            case 'commerce':
                $commerce = $this->commerce(['id', 'user_id']);
                $userRoleId = $commerce->id;
                break;
            case 'customer':
                $customer = $this->customer(['id', 'user_id']);
                $userRoleId = $customer->id;
                break;
            case 'seller':
                $seller = $this->seller(['id', 'user_id']);
                $userRoleId = $seller->id;
                break;
            default:
                # code...
                break;
        }

        return ['type' => $userRole, 'id' => $userRoleId, 'userId' => $userId];
    }

    public function insertOtherCurrencies($creatorUser, $otherCurrencies)
    {
        if (empty($otherCurrencies) || !is_array($otherCurrencies)) return;

        $creatorId = $creatorUser['id'];
        $modelName = $creatorUser['model'];
        $userColumn = strtolower($modelName);
        $model = app("App\\Models\\{$modelName}CountriesCurrencies");

        $otherCurrencies = Currency::getCurrenciesById($otherCurrencies, ['id', 'country']);
        $currenciesToCreate = [];
        $currencyIds = [];
        $timestamp = now();

        foreach ($otherCurrencies as $currency) {
            $currencyIds[] = $currency->id;
            $currenciesToCreate[] = [
                $userColumn => $creatorId,
                'country' => $currency->country,
                'currency' => $currency->id,
                'level' => '2',
                'creator_user_id' => $creatorUser['userId'],
                'status' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
        }

        DB::transaction(function () use ($model, $userColumn, $creatorId, $currenciesToCreate, $currencyIds) {
            try {
                foreach ($currenciesToCreate as $currencyData) {
                    $model::firstOrCreate(
                        [$userColumn => $creatorId, 'currency' => $currencyData['currency']],
                        $currencyData
                    );
                }

                $model::where($userColumn, $creatorId)
                    ->whereIn('currency', $currencyIds)
                    ->update(['status' => 1]);

                $model::where($userColumn, $creatorId)
                    ->where('level', '2')->whereNotIn('currency', $currencyIds)
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

        $creatorId = $creatorUser['id'];
        $modelName = $creatorUser['model'];
        $userColumn = strtolower($modelName);
        $model = app("App\\Models\\{$modelName}CountriesCurrencies");

        $allowedGames = Currency::getCurrenciesById($allowedGames, ['id', 'country']);
        $currenciesToCreate = [];
        $currencyIds = [];
        $timestamp = now();

        foreach ($allowedGames as $currency) {
            $currencyIds[] = $currency->id;
            $currenciesToCreate[] = [
                $userColumn => $creatorId,
                'country' => $currency->country,
                'currency' => $currency->id,
                'level' => '2',
                'creator_user_id' => $creatorUser['userId'],
                'status' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
        }

        DB::transaction(function () use ($model, $userColumn, $creatorId, $currenciesToCreate, $currencyIds) {
            try {
                foreach ($currenciesToCreate as $currencyData) {
                    $model::firstOrCreate(
                        [$userColumn => $creatorId, 'currency' => $currencyData['currency']],
                        $currencyData
                    );
                }

                $model::where($userColumn, $creatorId)
                    ->whereIn('currency', $currencyIds)
                    ->update(['status' => 1]);

                $model::where($userColumn, $creatorId)
                    ->where('level', '2')->whereNotIn('currency', $currencyIds)
                    ->update(['status' => 0]); // monedas no seleccionadas = inactivas

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error al insertar monedas para el $userColumn: " . $e->getMessage());
                throw $e;
            }
        });
    }
}
