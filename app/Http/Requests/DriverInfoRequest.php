<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverInfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_id' => 'required|integer'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
