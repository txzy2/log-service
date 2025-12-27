<?php

namespace App\Http\Requests;

use App\Enums\ErrorsEnum;
use App\Enums\LevelsEnum;
use App\Traits\RespondsWithMessages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreLogRequest extends FormRequest {
    use RespondsWithMessages;

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'level' => ['required', Rule::in(LevelsEnum::cases())],
            'service' => 'required|string',
            'message' => 'required|string',
            'domain' => 'required|string',
            'action' => 'required|string',
            'function' => 'required|string',
            'additionalFields' => 'nullable|array',
            'file' => 'required|string',
            'class' => 'required|string',
            'date' => 'required|date',
            'hash_sum' => 'required|string',
        ];
    }

    public function messages(): array {
        return [
            'required' => 'Поле :attribute обязательно для заполнения',
            'additionalFields.array' => 'Неверный тип для additionalFields. ожидается массив',
            'level.in' => 'Переданный статус не валиден',
        ];
    }

    /**
     * Переопределяем обработку ошибок валидации
     */
    protected function failedValidation(Validator $validator) {
        Log::channel('debug')->warning(
            'StoreLogRequest::VALIDATION ERROR',
            $validator->errors()->all()
        );

        $firstError = $validator->errors()->first();
        throw new HttpResponseException(
            $this->sendError($firstError, ErrorsEnum::VALIDATION_ERROR->value)
        );
    }
}
