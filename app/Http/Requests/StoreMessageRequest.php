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
            'recipient' => ['required', 'string'],
            'subject' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'provider_slug' => ['nullable', 'string', 'exists:message_providers,slug'],
        ];
    }

    public function validatedPayload(): array
    {
        return $this->validated();
    }
}
