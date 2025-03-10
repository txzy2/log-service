<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IncidentController extends Controller
{
    private const ERROR_CLASS = __CLASS__;

    /**
     * addType - добавляет новый тип инцидента в БД
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addType(Request $request)
    {
        $data = $request->all();

        Log::channel('debug')->info(self::ERROR_CLASS . '::addType REQUST DATA', [$data]);
        $validated = Validator::make(
            $data,
            [
                'type_name' => 'required|string',
                'send_template_id' => 'nullable|int|min:0|not_in:0',
                'code' => 'required|string',
                'lifecycle' => 'required|int|min:0|not_in:0'
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
            ]
        );

        if ($validated->fails()) {
            return $this->sendError($validated->errors()->first(), 400);
        }

        $addData = IncidentType::validateAndAddType($data);
        return $this->sendResponse($addData['message'], $addData['data'], $addData['success']);
    }
}
