<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class TestConroller extends Controller
{
    public function test(): JsonResponse
    {
        return parent::sendResponse();
    }
}