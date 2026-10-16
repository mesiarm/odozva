<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'position' => ['nullable', 'string', 'max:255'],
            'future' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
