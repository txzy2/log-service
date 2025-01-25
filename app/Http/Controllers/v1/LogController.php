<?php

namespace App\Http\Controllers\v1;

use App\Helpers\Init;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $parcedData = Init::returnParts($data);
        Log::channel("debug")->info('\LogController::sendLog REQUEST', $parcedData);

        $serviceObject = Init::initServiceObject($parcedData['service']);
        $return = $serviceObject->logging($data);

        return parent::sendResponse($return);
    }

}