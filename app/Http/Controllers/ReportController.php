<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * sendReport - контроллер для формирования отчетов по логам
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function report(Request $request): JsonResponse
    {
        $data = $request->all();
        Log::channel('debug')->info(static::getControllerClass() . '::sendReport REQUEST', $data);
        $validate = Validator::make(
            $data,
            [
                'service' => 'nullable|string',
                'code' => 'nullable|string',
                'date' => 'nullable|string',
                'demo' => 'nullable|string',
                'offset' => 'nullable|integer|min:0',
                'limit' => 'nullable|integer|min:1|max:100',
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
            ],
        );

        if ($validate->fails()) {
            Log::channel('debug')->warning(static::getControllerClass() . '::sendReport VALIDATION ERROR', $validate->errors()->all());
            return $this->sendError($validate->errors(), 400);
        }

        $return = Incident::getIncidentDataByParams($data);
        return match ($return['success']) {
            true => $this->sendSuccess($return['message'], $return['data']),
            default => $this->sendError($return['message'], 400),
        };
    }
}
