<?php

namespace Database\Factories;

use App\Enums\LeadPlatform;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'company' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->sentence(),
            'status' => 'new',
            'source' => 'landing_page',
            'platform' => LeadPlatform::Website,
        ];
    }

    /**
     * Indicate that an admin entered the lead manually from the given platform.
     */
    public function manual(LeadPlatform $platform): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => 'manual',
            'platform' => $platform,
        ]);
    }

    /**
     * Indicate that the lead has the given status.
     */
    public function status(string $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }
}
