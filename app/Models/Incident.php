<?php

namespace App\Models;

use App\Enums\LevelsEnum;
use App\Events\IncidentCreated;
use App\Events\IncidentUpdatedAfterLifecycle;
use App\Helpers\Parsers\Parser;
use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Incident extends BaseModel
{
    use HasFactory;
    use HasUuids;

    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    protected $table = 'incident';
    protected $fillable = [
        'domain',
        'service',
        'message',
        'class',
        'function',
        'action',
        'file',
        'additionalFields',
        'incident_type_id',
        'date',
        'count',
        'hash_sum',
        'level',
        'demo',
    ];

    protected $casts = [
        'additionalFields' => 'array',
    ];

    /**
     * Создаёт Incident из массива данных (из FormRequest)
     * (Information Expert - объект знает, как себя создать)
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $incident = new self();
        $incident->fill([
            'level'            => $data['level'],
            'service'          => $data['service'],
            'message'          => $data['message'],
            'domain'           => $data['domain'],
            'action'           => $data['action'],
            'function'         => $data['function'],
            'file'             => $data['file'] ?? '',
            'class'            => $data['class'] ?? '',
            'date'             => $data['date'],
            'hash_sum'         => $data['hash_sum'],
            'demo'             => $data['demo'] ?? 'N',
            'additionalFields' => $data['additionalFields'] ?? null,
        ]);

        return $incident;
    }

    public static function getIncidentDataByParams(array $params): array
    {
        $return = [
            'success' => false,
            'message' => 'Данные не найдены',
            'data'    => [],
        ];

        $query = static::query()
            ->leftJoin('incident_type', 'incident.incident_type_id', '=', 'incident_type.id')
            ->select([
                'incident.uuid',
                'incident.message',
                'incident.class',
                'incident.action',
                'incident.domain',
                'incident.date',
                'incident.count',
                'incident.service',
                'incident.demo',
                'incident.additionalFields',
                'incident.incident_type_id',
                'incident_type.type_name',
                'incident_type.code',
                'incident_type.lifecycle',
            ]);

        static::applyFilerByParam($params, $query, 'service', 'incident.service');
        static::applyFilerByParam($params, $query, 'date', 'incident.date');
        static::applyFilerByParam($params, $query, 'code', 'incident_type.code');
        static::applyFilerByParam($params, $query, 'demo', 'incident.demo');

        $offset = (int)($params['offset'] ?? 0);
        $limit = (int)($params['limit'] ?? 50);
        $limit = min($limit, 100);

        $query->skip($offset)->take($limit);

        $returnData = $query->get()->toArray();

        if (!empty($returnData)) {
            $return['success'] = true;
            $return['message'] = '';

            $return['data'] = array_map(function ($item) {
                return [
                    'id'       => $item['uuid'],
                    'domain'   => $item['domain'],
                    'action'   => $item['action'],
                    'incident' => [
                        'type'      => $item['type_name'] ?? 'unknown',
                        'code'      => $item['code'] ?? null,
                        'service'   => $item['service'],
                        'message'   => $item['message'],
                        'lifecycle' => $item['lifecycle'] ?? null,
                    ],
                    'additional_data' => $item['additionalFields'],
                    'count'           => $item['count'],
                    'date'            => $item['date'],
                    'has_type'        => $item['incident_type_id'] !== null,
                    'demo'            => $item['demo'],
                ];
            }, $returnData);
        }

        return $return;
    }

    /**
     * applyFilerByParam - Применение по полям
     *
     * @param array $params
     * @param mixed $query
     * @param string $paramKey
     * @param string $column
     * @return void
     */
    private static function applyFilerByParam(
        array  $params,
        mixed  $query,
        string $paramKey,
        string $column
    ): void {
        if (!empty($params[$paramKey])) {
            if (str_contains($column, '.date') || $column === 'date') {
                $query->whereDate($column, $params[$paramKey]);
            } else {
                $query->where($column, $params[$paramKey]);
            }
        }
    }

    /**
     * Обрабатывает инцидент согласно его типу
     * (Information Expert - объект знает, как себя обработать)
     *
     * @return array
     */
    public function process(): array
    {
        [$code, $message] = $this->parseCodeAndMessage();
        $existType = IncidentType::where('code', $code)->first();
        $this->message = $message;

        Log::channel("debug")->info(static::getModelClass() . "::process DATA", [$this]);

        return match (true) {
            $existType === null || $this->demo === 'Y' => $this->saveAsUnknown(),
            default                                    => $this->processWithType($existType),
        };
    }

    /**
     * Парсит код и сообщение из message
     *
     * @return array [code, message]
     */
    protected function parseCodeAndMessage(): array
    {
        if (LevelsEnum::from($this->level) !== LevelsEnum::INFO) {
            return Parser::parseStr($this->message);
        }

        return ['', $this->message];
    }

    /**
     * saveAsUnknown - сохраняем логи, о которых мы ещё не знаем
     *
     * @return array
     */
    protected function saveAsUnknown(): array
    {
        Log::channel('unknown_errors')->warning(
            "Новая не отслеживаемая ошибка от {$this->service}: " . json_encode(
                $this->toArray(),
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );

        unset($this->hash_sum);

        $this->incident_type_id = null;
        $this->count = 1;
        $this->save();

        return [
            'success' => true,
            'message' => 'Данные успешно сохранены',
        ];
    }

    /**
     * processWithType - обрабатываем инцидент с известным типом
     *
     * @param IncidentType $incidentType
     * @return array{message: string, success: bool}
     */
    protected function processWithType(IncidentType $incidentType): array
    {
        if (!$this->isValidHash()) {
            Log::channel('debug')->error(static::getModelClass() . ' invalid hash', [
                'service' => $this->service,
                'hash'    => $this->hash_sum,
            ]);
            return [
                'success' => false,
                'message' => 'Контрольная сумма не совпадает',
            ];
        }

        try {
            $existingIncident = static::where('hash_sum', $this->hash_sum)->first();
            if (!$existingIncident) {
                $this->incident_type_id = $incidentType->id;
                $this->count = 1;
                $this->save();

                event(new IncidentCreated($this, $incidentType));

                return [
                    'success' => true,
                    'message' => 'Данные успешно сохранены и отправлены',
                ];
            }

            return $this->handleExistingIncident($existingIncident, $incidentType);
        } catch (Exception $e) {
            Log::channel('debug')->error('EXCEPTION save', [$e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Ошибка при сохранении: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Проверяет валидность хэша
     *
     * @return bool
     */
    protected function isValidHash(): bool
    {
        return $this->hash_sum === $this->generateHash();
    }

    /**
     * Генерирует хэш инцидента
     *
     * @return string
     */
    protected function generateHash(): string
    {
        return hash(
            'sha256',
            $this->service . $this->action . $this->function . $this->level
        );
    }

    /**
     * handleExistingIncident - обрабатывает существующий инцидент
     *
     * @param Incident $existIncident
     * @param IncidentType $incidentType
     * @return array{message: string, success: bool}
     */
    protected function handleExistingIncident(Incident $existIncident, IncidentType $incidentType): array
    {
        $parseDates = Parser::parseDates($existIncident->date, $this->date);
        $existIncident->count++;

        if ($parseDates['prevDate']->diffInDays($parseDates['currentDate'], true) >= $existIncident->incidentType->lifecycle) {
            $existIncident->date = $parseDates['currentDate'];
            $existIncident->save();

            //NOTE: Listener для отправки Push/Email
            event(new IncidentUpdatedAfterLifecycle($existIncident, $incidentType));

            return [
                'success' => true,
                'message' => 'Данные успешно обновлены',
            ];
        }

        $existIncident->save();
        return [
            'success' => true,
            'message' => "Ошибка уже отправлялась ID ошибки: {$existIncident->id}",
        ];
    }

    /*
     * getIncidentDataByParams - получаем данные по параметрам
     *
     * @param array $data
     * @return array
     *
     * */

    public function incidentType()
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }
}
