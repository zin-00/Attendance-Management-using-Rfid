<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // return [
        //     'rfid_tag' => Str::uuid()->toString(),
        //     'first_name' => $this->faker->firstName,
        //     'last_name' => $this->faker->lastName,
        //     'birthdate' => $this->faker->date('Y-m-d', '-18 years'),
        //     'address' => $this->faker->address,
        //     'contact_number' => $this->faker->phoneNumber,
        //     'email' => $this->faker->unique()->safeEmail,
        //     'gender' => $this->faker->randomElement(['Male', 'Female', 'Other']),
        //     'status' => $this->faker->randomElement(['Active', 'Inactive', 'Resigned', 'Banned']),
        //     'position_id' => Position::factory(),
        //     'created_at' => now(),
        //     'updated_at' => now(),
        //];

            return [
                'rfid_tag' => $this->faker->unique()->uuid,
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'birthdate' => $this->faker->date(),
                'street_address' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->state,
                'zip_code' => $this->faker->postcode,
                'country' => $this->faker->country,
                'hire_date' => $this->faker->date(),
                'contact_number' => $this->faker->phoneNumber,
                'emergency_contact' => $this->faker->name,
                'emergency_contact_number' => $this->faker->phoneNumber,
                'email' => $this->faker->unique()->safeEmail,
                'password' => bcrypt('password'), // Default password
                'gender' => $this->faker->randomElement(['Male', 'Female', 'Other']),
                'status' => $this->faker->randomElement(['Active', 'Inactive', 'Resigned', 'Banned']),
                'position_id' => Position::factory(),
            ];
    }
}
