<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscordSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('standings.admin') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'webhook_url' => ['nullable', 'url', 'starts_with:https://'],
            'role_id' => ['nullable', 'string', 'regex:/^\d+$/'],
        ];
    }
}
