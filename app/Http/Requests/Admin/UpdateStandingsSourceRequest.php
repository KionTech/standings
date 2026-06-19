<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\StandingsSourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStandingsSourceRequest extends FormRequest
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
            'type' => ['required', Rule::enum(StandingsSourceType::class)],
        ];
    }
}
