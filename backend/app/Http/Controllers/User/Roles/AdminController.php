<?php

namespace App\Http\Controllers\User\Roles;

use App\Http\Controllers\Controller;
use App\Repositories\Countries\Contracts\CountryRepositoryContract;
use App\Repositories\Currencies\Contracts\CurrencyRepositoryContract;
use App\Repositories\Games\Contracts\GameRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceRepositoryContract;
use App\Repositories\Users\Shared\Contracts\TimeZoneRepositoryContract;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Users\UserTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminController extends Controller
{
    use UserTrait, EncryptTrait;

    /**
     * Repositorio de monedas
     */
    public function __construct(
        protected CommerceRepositoryContract $commerces,
        protected CountryRepositoryContract $countries,
        protected CurrencyRepositoryContract $currencies,
        protected GameRepositoryContract $games,
        protected TimeZoneRepositoryContract $timeZones
    ) {}

    /**
     * Vista de comercializadoras
     */
    public function commerces(): View
    {
        return view('modules.users.roles.admin.commerce.index', [
            'title' => 'Comercializadoras',
            'pageData' => [
                'currencies' => $this->currencies->getActive(),
                'commerces'  => $this->commerces->getAllWithRelations([
                    'country:id,name',
                    'user:id,email',
                ]),
                'countries' => $this->countries->getActive(),
                'games'     => $this->games->getActive(),
                'timeZones' => $this->timeZones->getActive(),
                'enableFormCrud' => true,
                'moduleFile' => 'modules/users/roles/admin/commerce/form/commerceForm.ts',
            ]
        ]);
    }

    /**
     * Vista de clientes
     */
    public function customers(): View
    {
        return view('modules/admin/customer/index', [
            'title'     => 'Clientes',
            'name'      => 'admin-customer',
            'customers' => Customer::with([
                'Country:id,name',
                'User:id,email'
            ])->get(),
            'countries' => Country::getAllAvailableCountries(),
            'timeZones' => $this->getActiveTimeZones(),
            'currencies' => Currency::getActive(),
        ]);
    }

    /**
     * Vista de tesoreros
     */
    public function treasurers(): View
    {
        return view('modules/admin/treasurer/index', [
            'title'      => 'Tesoreros',
            'name'       => 'admin-treasurer',
            'others'     => false,
            'treasurers' => Treasurer::with([
                'Country:id,name',
                'User:id,email'
            ])->get(),
            'countries' => Country::getAllAvailableCountries(),
            'timeZones' => $this->getActiveTimeZones(),
            'currencies' => Currency::getActive(),
            'games'     => Game::getAvailableGames(),
        ]);
    }

    /**
     * Vista de otros tesoreros
     */
    public function otherTreasurers(): View
    {
        return view('modules/admin/treasurer/other-treasurers', [
            'title'           => 'Otros Tesoreros',
            'name'            => 'admin-other-treasurers',
            'others'          => true,
            'otherTreasurers' => OtherTreasurers::with([
                'User:id,user_type,country,username',
                'User.Country:id,name'
            ])->get(),
            'currencies' => Currency::getActive(),
            'games'      => Game::getAvailableGames(),
        ]);
    }

    /**
     * Convierte un usuario existente en comercializadora
     */
    public function promoteUserToCommerce(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'user_id'  => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($request) {
                Commerce::create([
                    'name'      => $request->name,
                    'phone'     => $request->phone,
                    'currency'  => $request->currency,
                    'country'   => $request->country,
                    'status'    => 1,
                    'user'      => $request->user_id,
                ]);
            });

            return back()->with('message', 'Usuario promovido correctamente');
        } catch (\Throwable $e) {
            Log::error('Error promoviendo usuario a commerce', [
                'exception' => $e
            ]);

            return back()
                ->with('message', 'Error interno al promover el usuario')
                ->with('type', 'error');
        }
    }

    /**
     * Obtiene información de usuarios según rol
     *
     * @param string $userRole
     * @param string $exclude
     * @return \Illuminate\Support\Collection|array
     */
    public function getUserInfo(string $userRole, string $exclude = '')
    {
        if ($userRole === 'admin') {
            return [];
        }

        $excludedUsers = $this->resolveExcludedCreators($exclude);

        if ($userRole === 'commerce') {
            return Commerce::where('status', 1)
                ->when(
                    $excludedUsers,
                    fn($q) =>
                    $q->whereNotIn('user', $excludedUsers)
                )
                ->get(['name', 'user']);
        }

        return [];
    }

    /**
     * Timezones activos
     */
    protected function getActiveTimeZones()
    {
        return TimeZone::where('status', 1)->get();
    }

    /**
     * Obtiene usuarios creadores a excluir dinámicamente
     */
    protected function resolveExcludedCreators(string $model): array
    {
        if (!$model) {
            return [];
        }

        $modelClass = "App\\Models\\{$model}";

        if (class_exists($modelClass) && method_exists($modelClass, 'getAllCreatorUser')) {
            return $modelClass::getAllCreatorUser();
        }

        return [];
    }
}
