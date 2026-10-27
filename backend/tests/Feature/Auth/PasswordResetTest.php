<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_reset_with_valid_token() : void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function($notification) use ($user) {
            $response = $this->post('/resetPassword', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            // NewPasswordController::store() responde JSON (no redirect) a
            // propósito — ver su docblock: el formulario real hace POST por
            // axios, y un RedirectResponse siempre devolvía 200 a axios
            // incluso con token inválido, mostrando el toast de éxito sin
            // haber cambiado la contraseña.
            $response
                ->assertOk()
                ->assertJson(['success' => true]);

            return true;
        });
    }

    public function test_reset_password_link_can_be_requested() : void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_screen_can_be_rendered() : void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_screen_can_be_rendered() : void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function($notification) {
            $response = $this->get('/resetPassword/' . $notification->token);

            $response->assertStatus(200);

            return true;
        });
    }
}
