<?php

namespace App\Http\Controllers\v1\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller {
    public function reg(Request $request): JsonResponse {
        $data = $request->all();
        Log::channel("debug")->info(static::getControllerClass() . "::reg REQUEST DATA", $data);

        return $this->sendSuccess("SUCCESS");
    }

    public function token(Request $request): JsonResponse {
        $data = $request->all();
        Log::channel("debug")->info(static::getControllerClass() . "::token REQUEST DATA", $data);
        
        return $this->sendSuccess("SUCCESS");
    }
}
