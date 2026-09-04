<?php

namespace Tests\Feature;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Verifica el pipeline de excepciones registrado en bootstrap/app.php:
 * status por excepción (no un mapa centralizado), sin fuga del mensaje
 * crudo en producción para excepciones no reconocidas, y respuesta Inertia
 * (no JSON puro) para DomainException disparada en navegación normal.
 *
 * Rutas de prueba registradas inline (no en routes/) — cada test dispara
 * un tipo de excepción distinto de forma aislada, sin depender de flujos de
 * negocio reales (auth, CSRF, wallets...) que no son lo que este test verifica.
 */
class ExceptionHandlingTest extends TestCase
{
    public function test_domain_exception_custom_returns_its_declared_status_as_json(): void
    {
        Route::get('/__test/domain-exception', function () {
            throw BingoRoomException::notFound();
        })->middleware('api');

        $response = $this->getJson('/__test/domain-exception');

        $response->assertStatus(404);
        $response->assertJson([
            'success'    => false,
            'error_code' => 'BINGO_ROOM_NOT_FOUND',
        ]);
        $this->assertNotEquals('BINGO_ROOM_NOT_FOUND', $response->json('message'));
    }

    public function test_domain_exception_custom_renders_inertia_error_page_for_normal_navigation(): void
    {
        Route::get('/__test/domain-exception-web', function () {
            throw BingoRoomException::notFound();
        })->middleware('web');

        $response = $this->get('/__test/domain-exception-web', ['Accept' => 'text/html']);

        $response->assertStatus(404);
        $response->assertInertia(fn ($page) => $page
            ->component('Errors/Error')
            ->where('status', 404)
        );
    }

    public function test_native_domain_exception_returns_422_with_human_message(): void
    {
        Route::get('/__test/native-domain-exception', function () {
            throw new \DomainException('Saldo insuficiente en la billetera.');
        })->middleware('api');

        $response = $this->getJson('/__test/native-domain-exception');

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Saldo insuficiente en la billetera.',
        ]);
    }

    public function test_unrecognized_exception_message_is_sanitized_in_production(): void
    {
        // app()->environment() lee el binding 'env' del contenedor
        // (poblado una vez al bootstrapear desde APP_ENV), NO config('app.env')
        // — config() no alcanza a bootstrap/app.php's withExceptions(), que
        // llama app()->environment('production') directamente.
        $this->app['env'] = 'production';

        Route::get('/__test/query-exception', function () {
            throw new \RuntimeException('SQLSTATE[42S02]: Table `wallets`.`secret_column` not found — leaked SQL detail');
        })->middleware('api');

        $response = $this->getJson('/__test/query-exception');

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Ocurrió un error inesperado. Por favor intenta nuevamente.',
        ]);
        $this->assertStringNotContainsString('SQLSTATE', (string) $response->json('message'));
    }

    public function test_unrecognized_exception_message_is_raw_outside_production(): void
    {
        Route::get('/__test/query-exception-dev', function () {
            throw new \RuntimeException('Mensaje técnico real para depurar en dev.');
        })->middleware('api');

        $response = $this->getJson('/__test/query-exception-dev');

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Mensaje técnico real para depurar en dev.',
        ]);
    }

    public function test_http_exception_like_status_below_500_is_never_sanitized_even_in_production(): void
    {
        $this->app['env'] = 'production';

        Route::get('/__test/not-found-model', function () {
            \App\Models\User::findOrFail('0000000000000000000');
        })->middleware('api');

        $response = $this->getJson('/__test/not-found-model');

        $response->assertStatus(404);
        $this->assertNotEquals(
            'Ocurrió un error inesperado. Por favor intenta nuevamente.',
            $response->json('message')
        );
    }
}
