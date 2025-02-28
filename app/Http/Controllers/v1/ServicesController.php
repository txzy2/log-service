<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Services;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        return $this->sendResponse('', $services);
    }

    /**
     * editService - Редактирование сервиса
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function editService(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'active' => 'required|in:Y,N',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения',
            'active.in' => 'Поле :attribute должно быть Y или N',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

        $data = $request->all();

        $service = Services::findService($data['name']);
        if (!$service) {
            return $this->sendError('Сервис не найден', 400);
        }

        Services::where('name', $data['name'])->update(['active' => $data['active']]);
        Log::channel('debug')->info(self::ERROR_CLASS . "::editService", ["{$data['name']}" => $data['active']]);
        return $this->sendResponse('Сервис успешно отредактирован');
    }

    public function deleteService(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения',
            'service.string' => 'Невалидный тип данных',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

        $data = $request->all();
        $existService = Services::where('name', $data['service'])->first();
        if (!$existService) {
            return $this->sendError('Сервис не найден', 400);
        }

        $existService->delete();
        Log::channel('debug')->info(self::ERROR_CLASS . "::deleteService", ["{$data['service']} deleted"]);

        return $this->sendResponse('Сервис успешно удален', Services::all()->toArray());
    }
}
