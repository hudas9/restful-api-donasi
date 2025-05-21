<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ReportComment;
use App\Models\Report;
use App\Models\User;

class ReportCommentFactory extends Factory
{
    protected $model = ReportComment::class;

    public function definition(): array
    {
        return [
            'comment' => $this->faker->sentence,
            'report_id' => Report::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
        ];
    }
}
