<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiIpListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IpsController extends Controller
{
    public function json(ApiIpListService $ips): JsonResponse
    {
        return response()
            ->json($ips->payload())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function js(ApiIpListService $ips): Response
    {
        $json = json_encode($ips->payload(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return response(
            'window.CrynovaApiIps = '.$json.';',
            200,
            [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ],
        );
    }
}
