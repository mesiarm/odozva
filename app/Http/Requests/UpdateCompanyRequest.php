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
            'location' => ['nullable', 'string', 'max:255'],
            'sent_at' => ['nullable', 'date', 'before_or_equal:today'],
            'position' => ['nullable', 'string', 'max:255'],
            'future' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
