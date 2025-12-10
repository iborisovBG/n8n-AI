<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdScriptTask>
 */
class AdScriptTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_script' => fake()->paragraphs(3, true),
            'outcome_description' => fake()->sentence(),
            'new_script' => null,
            'analysis' => null,
            'status' => 'pending',
            'error_details' => null,
        ];
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'new_script' => fake()->paragraphs(3, true),
            'analysis' => fake()->paragraph(),
        ]);
    }

    /**
     * Indicate that the task failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_details' => fake()->sentence(),
        ]);
    }
}
