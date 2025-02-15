<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Services;
use Illuminate\Support\Facades\Validator;

class ServicesController extends Controller
{
    /**
     * getServices - Получение всех сервисов
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServices()
    {
        $services = Services::all()->toArray();
        return $this->sendResponse('', $services);
    }

    /**
     * editService - Редактирование сервиса
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function editService()
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required|integer',
            'active' => 'required|in:Y,N',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения',
            'active.in' => 'Поле :attribute должно быть Y или N',
        ]);

        if ($validator->fails()) {      
            return $this->sendError($validator->errors()->first(), 400);
        }

        $service = Services::find(request('id'));
        if (!$service) {
            return $this->sendError('Сервис не найден', 404);
        }
        
        if ($service->active === request('active')) {
            return $this->sendResponse('Сервис уже имеет такой статус');
        }

        $service->update(['active' => request('active')]);

        return $this->sendResponse('Сервис успешно отредактирован');
    }

    /**
     * addService - Добавление сервиса
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function addService()
    {
        $data = request()->all();

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'active' => 'required|in:Y,N',
        ], [
            '*.required' => 'Поле :attribute обязательно для заполнения', 
            'active.in' => 'Поле :attribute должно быть Y или N',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

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
