<?php

namespace App\Http\Requests\Licensing;

use Illuminate\Foundation\Http\FormRequest;

class GenerateLicenseKeysRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Belt-and-suspenders: the controller itself also hard-checks
        // isSuperAdmin() directly (see LicenseKeyController), so a
        // misconfigured permission can never let a company admin through.
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
