<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    /**
     * sendLog - запись лога
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendLog(Request $request)
    {
        $data = parent::unsetToken($request->all());
        Log::channel("debug")->info('\LogController::sendLog REQUEST', $data);

        return parent::sendResponse();
    }

}