<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('whatsapp_notifications_enabled')) {
            $this->merge([
                'whatsapp_notifications_enabled' => $this->boolean('whatsapp_notifications_enabled'),
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isOwner() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:150'],
            'store_address' => ['required', 'string', 'max:1000'],
            'store_whatsapp_number' => ['required', 'string', 'max:50'],
            'invoice_footer_note' => ['nullable', 'string', 'max:1000'],
            'primary_color' => ['required', 'hex_color'],
            'whatsapp_notifications_enabled' => ['sometimes', 'boolean'],
            'whatsapp_rental_message_template' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
