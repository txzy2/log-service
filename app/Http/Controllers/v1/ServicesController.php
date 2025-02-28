<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Services;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServicesController extends Controller
{
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
            'id' => 'required|integer',
            'active' => 'required|in:Y,N',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения',
            'active.in' => 'Поле :attribute должно быть Y или N',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

        $data = $request->all();

        $service = Services::find($data['id']);
        if (!$service) {
            return $this->sendError('Сервис не найден');
        }

        if ($service->active === $data['active']) {
            return $this->sendResponse('Сервис уже имеет такой статус');
        }

        Services::where('id', $data['id'])->update(['active' => $data['active']]);

        return $this->sendResponse('Сервис успешно отредактирован');
    }

    /**
     * addService - Добавление сервиса
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addService(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:16',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

        $data = $request->all();
        $existService = Services::where('name', $data['name'])->first();
        if ($existService) {
            return $this->sendError('Сервис с таким именем уже существует', 400);
        }

        Services::create(
            [
                'name' => $data['name'],
                'active' => "N",
            ]
        );

        return $this->sendResponse('Сервис успешно добавлен и взят на рассмотрение');
    }
}
