<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Services;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServicesController extends Controller
{
    private const ERROR_CLASS = __CLASS__;

    /**
     * getServices - Получение всех сервисов
     *
     * @return JsonResponse
     */
    public function getServices(): JsonResponse
    {
        $services = Services::all()->toArray();
        $incidentTypes = DB::table('incident_type')->select('type_name', 'code', 'lifecycle')->get()->toArray();
        return $this->sendResponse('', ['services' => $services, 'incidentTypes' => $incidentTypes]);
    }

    /**
     * editService - Редактирование сервиса
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function editService(Request $request): JsonResponse
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'active' => 'required|in:Y,N',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения',
            'active.in' => 'Поле :attribute должно быть Y или N',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

        $existService = Services::findService($data['service']);
        if (!$existService['success']) {
            return $this->sendError('Сервис не найден', 400);
        }

        Services::where('name', $data['name'])->update(['active' => $data['active']]);
        return $this->sendResponse('Сервис успешно отредактирован');
    }

    /**
     * deleteService - удаление сервиса
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function deleteService(Request $request): JsonResponse
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'service' => 'required|string',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения',
            'service.string' => 'Невалидный тип данных',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

        $existService = Services::findService($data['service']);
        if (!$existService['success']) {
            return $this->sendError('Сервис не найден', 400);
        }
        $existService->delete();

        Log::channel('debug')->info(self::ERROR_CLASS . "::deleteService", ["{$data['service']} deleted"]);
        return $this->sendResponse('Сервис успешно удален', Services::all()->toArray());
    }
}
