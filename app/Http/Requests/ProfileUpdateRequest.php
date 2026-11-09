<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->user_id;

        return [
            'username'         => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId, 'user_id')],
            'whatsapp_number'  => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId, 'user_id')],
            'name'             => ['nullable', 'string', 'max:255'],
            'shop_name'        => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'string'],
            'password'         => ['nullable', 'string', 'min:6'], // Diubah ke minimal 6 karakter
        ];
    }
}