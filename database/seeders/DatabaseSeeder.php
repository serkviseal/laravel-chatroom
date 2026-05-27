<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $alice = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $bob = User::factory()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        $general = Room::factory()->create(['name' => 'general', 'description' => 'General discussion']);
        $random = Room::factory()->create(['name' => 'random', 'description' => 'Off-topic chat']);

        $general->join($alice);
        $general->join($bob);
        $random->join($alice);

        Message::factory(20)->create(['room_id' => $general->id, 'user_id' => $alice->id]);
        Message::factory(15)->create(['room_id' => $general->id, 'user_id' => $bob->id]);
        Message::factory(10)->create(['room_id' => $random->id, 'user_id' => $alice->id]);
    }
}
