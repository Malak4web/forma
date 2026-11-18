<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'phone' => '010'.fake()->unique()->numerify('########'),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'goal' => 'loss',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['is_admin' => true])->afterMaking(fn (User $u) => $u->is_admin = true);
    }
}
