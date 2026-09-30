<?php

namespace Database\Seeders;

use App\Enums\LeadPlatform;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Seed the leads table with fake leads spread across statuses, sources, and dates.
     */
    public function run(): void
    {
        Lead::factory()
            ->count(50)
            ->state(function (): array {
                $createdAt = fake()->dateTimeBetween('-60 days');
                $source = fake()->randomElement(['landing_page', 'manual', 'outbound']);

                return [
                    'message' => fake()->paragraph(),
                    'status' => fake()->randomElement(['new', 'contacted', 'follow_up', 'won', 'lost']),
                    'source' => $source,
                    'platform' => $source === 'landing_page'
                        ? LeadPlatform::Website
                        : fake()->randomElement(array_filter(
                            LeadPlatform::cases(),
                            fn (LeadPlatform $platform): bool => $platform !== LeadPlatform::Website,
                        )),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            })
            ->create()
            ->each(fn (Lead $lead) => $this->addNotes($lead));
    }

    /**
     * Give a lead a few notes from the admins, spread between when it arrived and now.
     */
    private function addNotes(Lead $lead): void
    {
        $admins = User::all();

        $count = fake()->numberBetween(0, 4);

        for ($i = 0; $i < $count; $i++) {
            $at = fake()->dateTimeBetween($lead->created_at);

            LeadNote::factory()->for($lead)->for($admins->random())->create([
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }
    }
}
