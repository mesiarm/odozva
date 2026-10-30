<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'name_key' => Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString(),
            'source' => fake()->randomElement(['finstat', 'azet']),
            'address' => fake()->streetAddress(),
            'city' => 'Žilina',
            'web' => null,
            'ico' => null,
            'employees' => null,
            'revenue' => null,
            'founded_on' => null,
            'registry_status' => null,
            'status_note' => null,
        ];
    }
}
