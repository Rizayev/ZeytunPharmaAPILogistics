<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSimpleOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'route_ids' => 'required|array',
            'unit_id' => 'required|integer'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
