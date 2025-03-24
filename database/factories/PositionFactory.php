<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Software Engineer',
                'Project Manager',
                'HR Specialist',
                'Marketing Manager',
                'Data Analyst',
                'Customer Service Representative',
                'Accountant',
                'Sales Executive',
                'Graphic Designer',
                'IT Support Specialist'
            ]), // Real job titles
            'description' => $this->faker->paragraph(3), // Random job description
            'salary' => $this->faker->randomFloat(2, 50000, 500000), // Salary range
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
    }
}
