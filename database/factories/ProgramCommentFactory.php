<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProgramComment;
use App\Models\Program;
use App\Models\User;

class ProgramCommentFactory extends Factory
{
    protected $model = ProgramComment::class;

    public function definition(): array
    {
        return [
            'comment' => $this->faker->sentence,
            'program_id' => Program::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
        ];
    }
}
