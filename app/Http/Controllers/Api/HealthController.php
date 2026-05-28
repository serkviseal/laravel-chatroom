<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $status = ['status' => 'ok', 'db' => 'ok', 'redis' => 'ok'];
        $httpCode = 200;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $status['db'] = 'error: '.$e->getMessage();
            $status['status'] = 'degraded';
            $httpCode = 503;
        }

        try {
            Redis::ping();
        } catch (\Throwable $e) {
            $status['redis'] = 'error: '.$e->getMessage();
            $status['status'] = 'degraded';
            $httpCode = 503;
        }

        return response()->json($status, $httpCode);
    }
}
