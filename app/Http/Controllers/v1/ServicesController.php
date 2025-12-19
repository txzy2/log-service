<?php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Services;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $incidentTypes = DB::table('incident_type')->select('type_name', 'code', 'lifecycle')->get()->toArray();
        return $this->sendSuccess('', ['services' => $services, 'incidentTypes' => $incidentTypes]);
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
        if (! $existService['success']) {
            return $this->sendError('Сервис не найден', 400);
        }

        Services::where('name', $data['name'])->update(['active' => $data['active']]);
        return $this->sendSuccess('Сервис успешно отредактирован');
    }
}
