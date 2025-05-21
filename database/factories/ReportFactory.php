<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Report;
use App\Models\User;
use App\Models\Category;
use App\Models\Program;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->unique()->slug,
            'summary' => $this->faker->paragraph,
            'content' => $this->faker->text(1000),
            'program_id' => Program::factory(),
            'category_id' => Category::factory(),
            'image' => $this->faker->optional()->imageUrl(),
            'total_funds_used' => $this->faker->randomFloat(2, 1000, 1000000),
            'report_date' => $this->faker->date(),
            'beneficiaries' => $this->faker->optional()->randomElements([
                ['name' => $this->faker->name, 'amount' => $this->faker->numberBetween(1000, 10000)],
                ['name' => $this->faker->name, 'amount' => $this->faker->numberBetween(1000, 10000)]
            ], $this->faker->numberBetween(1, 2)),
            'is_published' => $this->faker->boolean,
            'user_id' => User::factory(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Report $report) {
            $count = $this->faker->numberBetween(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $report->documentations()->create([
                    'file_path' => $this->faker->imageUrl(),
                    'file_type' => $this->faker->randomElement(['image', 'video']),
                    'caption' => $this->faker->optional()->sentence,
                    'order' => $i
                ]);
            }
        });
    }
}
