<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Services;

class ServicesController extends Controller
{
    public function getServices()
    {
        $services = Services::all()->toArray();
        return $this->sendResponse('', $services);
    }

    public function addService()
    {
        $data = request()->all();
        $service = Services::create($data);
        return $this->sendResponse('', $service);
    }
}
