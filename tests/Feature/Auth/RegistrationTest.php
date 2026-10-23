<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('account.index', absolute: false));
        $this->assertDatabaseHas('customers', [
            'email' => 'test@example.com',
            'user_id' => User::query()->where('email', 'test@example.com')->value('id'),
        ]);
    }

    public function test_registration_links_an_existing_customer_by_case_insensitive_email(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Cliente anterior',
            'phone' => '525512345678',
            'email' => 'Cliente@Example.com',
            'city' => 'Puebla',
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'Cliente anterior',
            'email' => 'cliente@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('account.index', absolute: false));
        $user = User::query()->where('email', 'cliente@example.com')->firstOrFail();
        $this->assertSame($user->id, $customer->fresh()->user_id);
        $this->assertDatabaseCount('customers', 1);
    }
}
