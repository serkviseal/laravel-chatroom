<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'body' => fake()->sentence(),
            'type' => 'text',
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
        ];
    }
}
