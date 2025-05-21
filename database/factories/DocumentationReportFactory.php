<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DocumentationReport;

class DocumentationReportFactory extends Factory
{
    protected $model = DocumentationReport::class;

    public function definition(): array
    {
        return [
            'file_path' => $this->faker->imageUrl(),
            'file_type' => $this->faker->randomElement(['image', 'video']),
            'caption' => $this->faker->optional()->sentence,
            'order' => $this->faker->numberBetween(0, 10)
        ];
    }
}
