<?php

namespace App\Http\Requests\Licensing;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('licenses.activate') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
        ];
    }
}
