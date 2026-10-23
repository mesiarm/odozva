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
            'location' => ['nullable', 'string', 'max:255'],
            'sent_at' => ['nullable', 'date', 'before_or_equal:today'],
            'position' => ['nullable', 'string', 'max:255'],
            'web' => ['nullable', 'string', 'max:255'],
            'future' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
