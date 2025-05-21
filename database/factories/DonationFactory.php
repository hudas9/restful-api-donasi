<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;

class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->bothify('INV-#######'),
            'program_id' => Program::factory(),
            'user_id' => User::factory(),
            'donor_name' => $this->faker->name(),
            'donor_email' => $this->faker->unique()->safeEmail(),
            'donor_phone' => $this->faker->optional()->phoneNumber(),
            'donor_address' => $this->faker->optional()->address(),
            'amount' => $this->faker->randomFloat(2, 10000, 1000000),
            'message' => $this->faker->optional()->sentence(),
            'payment_method' => $this->faker->optional()->randomElement(['bank_transfer', 'credit_card', 'gopay', 'ovo']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed']),
            'midtrans_order_id' => $this->faker->optional()->uuid(),
            'midtrans_transaction_id' => $this->faker->optional()->uuid(),
            'midtrans_transaction_status' => $this->faker->optional()->randomElement(['settlement', 'pending', 'deny', 'expire']),
            'midtrans_payment_type' => $this->faker->optional()->randomElement(['bank_transfer', 'credit_card', 'gopay', 'ovo']),
            'midtrans_response_json' => $this->faker->optional()->text(200),
            'is_anonymous' => $this->faker->boolean(),
        ];
    }
}
