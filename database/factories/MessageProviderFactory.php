<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MessageProvider>
 */
class MessageProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(),
            'name' => $this->faker->company() . ' Provider',
            'provider' => $this->faker->randomElement(['smtp', 'log']),
            'config' => [
                'host' => $this->faker->domainName(),
                'port' => $this->faker->randomElement([587, 465, 25]),
                'username' => $this->faker->safeEmail(),
                'password' => $this->faker->password(),
            ],
            'is_default' => false,
            'messages_per_minute' => $this->faker->numberBetween(10, 100),
        ];
    }
}
