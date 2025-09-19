<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduledRequestedAt = $this->faker->dateTimeBetween('-1 week', '+1 month');

        return [
            'recipient' => $this->faker->safeEmail(),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'scheduled_at' => null,
            'scheduled_requested_at' => $scheduledRequestedAt,
            'sent_at' => null,
            'status' => 'pending',
            'message_provider_id' => null,
            'retry_counter' => 0,
        ];
    }
}
