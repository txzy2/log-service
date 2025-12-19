<?php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\IncidentTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Values\AddTypeData;

class IncidentController extends Controller
{
    protected IncidentTypeService $incidentTypeService;

    public function __construct(IncidentTypeService $incidentTypeService)
    {
        $this->incidentTypeService = $incidentTypeService;
    }

    /**
     * addType - добавляет новый тип инцидента в БД
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addType(Request $request)
    {
        $data = $request->all();

        Log::channel('debug')->info(static::getControllerClass() . '::addType REQUST DATA', [$data]);
        $validated = Validator::make(
            $data,
            [
                'type_name' => 'required|string',
                'send_template_id' => 'nullable|int|min:1',
                'code' => 'required|string',
                'lifecycle' => 'required|int|min:1',
                'send_to' => 'required|string'
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
            ]
        );

        if ($validated->fails()) {
            return $this->sendError($validated->errors()->first(), 400);
        }
        
        $addData = $this->incidentTypeService->create(AddTypeData::fromArray($data));
        return match ($addData['success']) {
            true    => $this->sendSuccess($addData['message'], $addData['data']),
            default => $this->sendError($addData['message'], 400)
        };
    }
}
