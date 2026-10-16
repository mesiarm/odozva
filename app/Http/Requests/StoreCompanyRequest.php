<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'channel' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'web' => ['nullable', 'string', 'max:255'],
            'future' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
