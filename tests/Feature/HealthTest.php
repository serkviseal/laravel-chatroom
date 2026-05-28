<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('health endpoint responds with db status', function () {
    $response = $this->getJson('/api/health');

    $response->assertJsonPath('db', 'ok');
    $response->assertJsonStructure(['status', 'db', 'redis']);
});
