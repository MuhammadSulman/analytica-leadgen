<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadNote>
 */
class LeadNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'body' => fake()->randomElement([
                'Sent an intro email with our CFO services overview.',
                'Called, no answer. Will try again tomorrow.',
                'Had a 20-minute discovery call. Main pain point is cash flow visibility.',
                'Shared a proposal and pricing.',
                'Client asked for a follow-up next week after their board meeting.',
                'Replied on the platform with availability for a call.',
            ]),
        ];
    }
}
