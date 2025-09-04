<?php
namespace Tests\Unit;

use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MiddlwareServiceTest extends TestCase
{
    private function getData(): array
    {
        return [
            'service'  => 'WSPG|Moneta',
            'incident' => [
                'object'      => '41231895',
                'object_data' => [
                    [
                        'key'   => 'КПП',
                        'value' => '2839482',
                    ],
                    [
                        'key'   => 'ИНН',
                        'value' => '2903492034',
                    ],
                    [
                        'key'   => 'Банковский счет',
                        'value' => '2342342903492034',
                    ],
                ],
                'message'     => [
                    'error' => [
                        'Envelope' => [
                            'Body' => [
                                'fault' => [
                                    'faultcode'   => 'Client',
                                    'faultstring' => 'Операции с данным счетом невозможны. Обратитесь в коммерческий отдел.',
                                    'detail'      => [
                                        'faultDetail' => '400.1.26',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'date'        => '2025-05-07',
            ],
        ];
    }

    /**
     * A basic unit test example.
     */
    public function success_message(): void
    {
        $data     = $this->getData();
        $response = $this->post("/api/v1/log", $data);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['success' => true]);
    }

    /**
     * Тест валидации данных (Введен сервис без | )
     */
    public function test_validation(): void
    {
        $invalidData            = $this->getData();
        $invalidData['service'] = 'invalid_service';

        $response = $this->postJson("/api/v1/log", $invalidData);

        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Неполный формат данных (service|type)']);
    }

    /**
     * Тест валидации данных (Введен не активный сервис)
     */
    public function test_inactive_service(): void
    {
        $service = \App\Models\Services::factory()->create([
            'name'   => 'TEST',
            'active' => 'N',
        ]);

        $data     = $this->getData();
        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);

        $service->delete();

        $this->assertDatabaseMissing('incident_services', [
            'id' => $service->id,
        ]);
    }

    /**
     * Тест валидации данных (Отсутствует поле service)
     */
    public function test_missing_service_field(): void
    {
        $data = $this->getData();
        unset($data['service']);

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Отсутствуют необходимые данные']);
    }

    /**
     * Тест валидации данных (Отсутствует поле incident)
     */
    public function test_missing_incident_field(): void
    {
        $data = $this->getData();
        unset($data['incident']);

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Отсутствуют необходимые данные']);
    }

    /**
     * Тест валидации данных (Пустое поле service)
     */
    public function test_empty_service_field(): void
    {
        $data            = $this->getData();
        $data['service'] = '';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Отсутствуют необходимые данные']);
    }

    /**
     * Тест валидации данных (Пустое поле type после разделителя)
     */
    public function test_empty_type_after_separator(): void
    {
        $data            = $this->getData();
        $data['service'] = 'WSPG|';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Неполный формат данных (service|type)']);
    }

    /**
     * Тест валидации данных (Пустое поле service до разделителя)
     */
    public function test_empty_service_before_separator(): void
    {
        $data            = $this->getData();
        $data['service'] = '|Moneta';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Неполный формат данных (service|type)']);
    }

    /**
     * Тест валидации данных (Несуществующий сервис)
     */
    public function test_nonexistent_service(): void
    {
        $data            = $this->getData();
        $data['service'] = 'NONEXISTENT|Moneta';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Введен неверный сервис или не активен']);
    }

    /**
     * Тест валидации данных (Множественные разделители |)
     */
    public function test_multiple_separators(): void
    {
        $data            = $this->getData();
        $data['service'] = 'WSPG|Moneta|Extra';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Введен неверный сервис или не активен']);
    }

    /**
     * Тест валидации данных (Сервис с пробелами)
     */
    public function test_service_with_spaces(): void
    {
        $data            = $this->getData();
        $data['service'] = ' WSPG | Moneta ';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Введен неверный сервис или не активен']);
    }

    /**
     * Тест валидации данных (Специальные символы в service)
     */
    public function test_service_with_special_characters(): void
    {
        $data            = $this->getData();
        $data['service'] = 'WSPG@#$|Moneta';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Введен неверный сервис или не активен']);
    }

    /**
     * Тест валидации данных (null значения)
     */
    public function test_null_values(): void
    {
        $data             = $this->getData();
        $data['service']  = null;
        $data['incident'] = null;

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Отсутствуют необходимые данные']);
    }

    /**
     * Тест валидации данных (Только пробелы в service)
     */
    public function test_service_with_only_spaces(): void
    {
        $data            = $this->getData();
        $data['service'] = '   |   ';

        $response = $this->postJson("/api/v1/log", $data);
        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Неполный формат данных (service|type)']);
    }

}
