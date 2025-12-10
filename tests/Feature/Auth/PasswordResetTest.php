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

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->withSession(['_token' => 'test-token'])
            ->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => $user->email,
                '_token' => 'test-token',
            ]);

        $response->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->withSession(['_token' => 'test-token'])
            ->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => $user->email,
                '_token' => 'test-token',
            ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get(route('password.reset', $notification->token));

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->withSession(['_token' => 'test-token'])
            ->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => $user->email,
                '_token' => 'test-token',
            ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->withSession(['_token' => 'test-token'])
                ->from(route('password.reset', $notification->token))
                ->post(route('password.update'), [
                    'token' => $notification->token,
                    'email' => $user->email,
                    'password' => 'newpassword',
                    'password_confirmation' => 'newpassword',
                    '_token' => 'test-token',
                ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login', absolute: false));

            return true;
        });
    }
}
