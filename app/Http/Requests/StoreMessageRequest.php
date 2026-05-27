<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required_without:attachment', 'string', 'max:2000'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'type' => ['sometimes', 'string', 'in:text,image,file'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
