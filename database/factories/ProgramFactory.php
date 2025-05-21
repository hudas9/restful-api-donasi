<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;
use App\Models\User;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'content' => $this->faker->text,
            'category_id' => Category::factory(),
            'image' => $this->faker->imageUrl(),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'target_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'collected_amount' => $this->faker->randomFloat(2, 0, 10000),
            'is_published' => $this->faker->boolean,
            'user_id' => User::factory(),
        ];
    }
}
