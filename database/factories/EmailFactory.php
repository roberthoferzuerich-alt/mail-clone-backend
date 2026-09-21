<?php

namespace Database\Factories;

use App\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Email>
 */
class EmailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender' => fake()->email(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(3),
            'is_read' => fake()->boolean(30), // 30% chance of being read
        ];
    }
}
