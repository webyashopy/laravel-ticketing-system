<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Webyashopy\Tickets\Tests\Stubs\User;

/**
 * Factory pro testovací stub User model.
 *
 * Žije v `tests/` (autoload `Webyashopy\Tickets\Tests\`) — není to produkční
 * factory balíčku, jen pomůcka pro Testbench feature testy. Stav `superAdmin()`
 * zapne cross-tenant viditelnost / roli ({@see User::isSuperAdmin()}).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'tenant_id' => null,
            'is_superadmin' => false,
        ];
    }

    /**
     * Uživatel s rolí superadmina (cross-tenant viditelnost + správa).
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => ['is_superadmin' => true]);
    }
}
