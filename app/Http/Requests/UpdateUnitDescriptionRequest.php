<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitDescriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'address_id' => 'required|integer',
            'guid' => 'required|string'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
