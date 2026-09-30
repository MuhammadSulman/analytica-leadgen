<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class BulkLeadStatusRequest extends BulkLeadRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['required', 'in:new,contacted,follow_up,won,lost'],
        ];
    }
}
