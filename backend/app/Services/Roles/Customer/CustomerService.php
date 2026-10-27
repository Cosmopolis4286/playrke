<?php

namespace App\Services\Users\Roles\Customer;

use App\Contracts\UserRepositoryInterface;
use App\Domains\Wallet\Models\Wallet;
use App\Models\Roles\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Servicio para manejar la lógica de negocio de Clientes (Customer).
 *
 * Maneja operaciones de:
 * - Creación de clientes con usuarios y wallets asociados
 * - Actualización de datos del cliente
 * - Obtención de información del cliente con sus relaciones
 * - Gestión de logos y archivos
 */
class CustomerService
{
    /**
     * Constructor del servicio.
     *
     * @param UserRepositoryInterface $userRepository Repositorio de usuarios
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Crear un nuevo cliente junto con usuario, wallet y configuraciones iniciales.
     *
     * Proceso:
     * 1. Genera una contraseña aleatoria
     * 2. Crea el usuario en la tabla users
     * 3. Asigna el rol 'customer'
     * 4. Crea el registro del cliente
     * 5. Crea el wallet principal
     * 6. Asocia monedas adicionales y juegos permitidos
     * 7. Procesa el logo si fue subido
     *
     * @param array $data Datos validados del request
     *
     * @return array ['customer_id' => Customer, 'password' => string]
     *
     * @throws Throwable Si ocurre algún error en la transacción
     */
    public function create(array $data) : array
    {
        return DB::transaction(function() use ($data) {
            $password = $this->generatePassword();

            // 👤 Crear usuario
            $user = $this->createUser($data, $password);

            // 🎭 Asignar rol de customer
            $user->assignRole('customer');

            // 🏢 Crear customer
            $customer = $this->createCustomerRecord($data, $user->id);

            // 💰 Wallet principal
            $this->createWallet($user->id, $data['currency']);

            // 💵 Asociar otras monedas si existen
            if (isset($data['other_currencies']) && is_array($data['other_currencies'])) {
                $customer->otherCurrencies()->attach($data['other_currencies']);
            }

            // 🎮 Asociar juegos permitidos si existen
            if (isset($data['allowed_games']) && is_array($data['allowed_games'])) {
                $customer->allowedGames()->attach($data['allowed_games']);
            }

            // 🖼️ Procesar logo si fue subido
            if (isset($data['logo']) && $data['logo']) {
                $logoPath = $this->storeLogo($data['logo'], $customer->id);
                $customer->update(['logo' => $logoPath]);
            }

            return ['customer_id' => $customer, 'password' => $password];
        });
    }

    /**
     * Obtener todos los clientes con sus relaciones.
     *
     * @return \Illuminate\Database\Eloquent\Collection Colección de clientes
     */
    public function getAll()
    {
        return Customer::with([
            'country:id,name',
            'user:id,username,email',
            'currency:id,symbol',
        ])->get();
    }

    /**
     * Obtener un cliente por ID con sus relaciones.
     *
     * @param string $id ID del cliente
     *
     * @return array Datos del cliente estructurados
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si no se encuentra el cliente
     */
    public function getById(string $id) : array
    {
        $customer = Customer::select([
            'id',
            'name',
            'customer_id',
            'phone',
            'country_id',
            'currency_id',
            'user_id',
            'api_route',
            'customer_key',
            'logo',
            'status',
            'created_at',
            'updated_at',
        ])->with([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currency:id,symbol,description',
            'otherCurrencies:id,symbol,description',
            'allowedGames:id,name',
        ])->findOrFail($id);

        return [
            'customer' => $customer,
            'user' => $customer->user,
            'other_currencies' => $customer->otherCurrencies->pluck('id')->toArray(),
            'allowed_games' => $customer->allowedGames->pluck('id')->toArray(),
        ];
    }

    /**
     * Actualizar un cliente existente.
     *
     * @param array  $data       Datos validados del DTO
     * @param string $customerId ID del cliente a actualizar
     *
     * @return Customer Cliente actualizado
     *
     * @throws Throwable Si ocurre algún error en la transacción
     */
    public function update(array $data, string $customerId) : Customer
    {
        return DB::transaction(function() use ($data, $customerId) {
            $customer = Customer::with('user')->findOrFail($customerId);

            // Actualizar datos del usuario
            $customer->user->update([
                'time_zone_id' => $data['time_zone'] ?? $customer->user->time_zone_id,
            ]);

            // Actualizar datos del customer
            $customer->update([
                'name' => $data['name'] ?? $customer->name,
                'customer_id' => $data['customer_id'] ?? $customer->customer_id,
                'phone' => $data['phone'] ?? $customer->phone,
                'country_id' => $data['country'] ?? $customer->country_id,
                'currency_id' => $data['currency'] ?? $customer->currency_id,
                'api_route' => $data['api_route'] ?? $customer->api_route,
                'customer_key' => $data['customer_key'] ?? $customer->customer_key,
            ]);

            // 💵 Sincronizar otras monedas
            if (isset($data['other_currencies'])) {
                $customer->otherCurrencies()->sync($data['other_currencies']);
            }

            // 🎮 Sincronizar juegos permitidos
            if (isset($data['allowed_games'])) {
                $customer->allowedGames()->sync($data['allowed_games']);
            }

            // 🖼️ Procesar logo si fue subido
            if (isset($data['logo']) && $data['logo']) {
                // Eliminar logo anterior si existe
                if ($customer->logo) {
                    Storage::disk('public')->delete($customer->logo);
                }

                $logoPath = $this->storeLogo($data['logo'], $customer->id);
                $customer->update(['logo' => $logoPath]);
            }

            return $customer->fresh();
        });
    }

    /**
     * Crear un registro de cliente en la tabla customers.
     *
     * @param array  $data   Datos del request
     * @param string $userId ID del usuario creado
     *
     * @return Customer Cliente creado
     */
    private function createCustomerRecord(array $data, string $userId) : Customer
    {
        return Customer::create([
            'name' => $data['name'],
            'customer_id' => $data['customer_id'],
            'phone' => $data['phone'],
            'currency_id' => $data['currency'],
            'country_id' => $data['country'],
            'api_route' => $data['api_route'],
            'customer_key' => $data['customer_key'],
            'status' => 'ACTIVE',
            'user_id' => $userId,
        ]);
    }

    /**
     * Crear un registro de usuario en la tabla users.
     *
     * @param array  $data     Datos del request
     * @param string $password Contraseña generada
     *
     * @return User Usuario creado
     */
    private function createUser(array $data, string $password) : User
    {
        return User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'country_id' => $data['country'],
            'time_zone_id' => $data['time_zone'],
            'email_verified_at' => Carbon::now(),
            'referral_code' => $this->userRepository->generateUniqueReferralCode(),
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Crear un wallet para el usuario con la moneda principal.
     *
     * @param string $userId     ID del usuario
     * @param string $currencyId ID de la moneda
     */
    private function createWallet(string $userId, string $currencyId) : void
    {
        Wallet::create([
            'currency_id' => $currencyId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Generar una contraseña aleatoria de 8 caracteres hexadecimales.
     *
     * @return string Contraseña generada
     */
    private function generatePassword() : string
    {
        return bin2hex(random_bytes(4));
    }

    /**
     * Almacenar el logo del cliente en storage.
     *
     * @param \Illuminate\Http\UploadedFile $file       Archivo subido
     * @param string                        $customerId ID del cliente
     *
     * @return string Ruta del archivo almacenado
     */
    private function storeLogo($file, string $customerId) : string
    {
        $filename = 'customer_' . $customerId . '_' . time() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs('customers/logos', $filename, 'public');
    }
}
