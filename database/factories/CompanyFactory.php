<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'status' => ApplicationStatus::Unsent,
            'sent_at' => null,
            'channel' => null,
            'position' => null,
            'web' => fake()->domainName(),
            'future' => null,
            'note' => null,
        ];
    }

    public function withStatus(ApplicationStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'sent_at' => $status === ApplicationStatus::Unsent ? null : fake()->dateTimeBetween('-2 months', 'now'),
            'channel' => $status === ApplicationStatus::Unsent ? null : fake()->randomElement(['Email', 'Profesia', 'Web']),
        ]);
    }
}
