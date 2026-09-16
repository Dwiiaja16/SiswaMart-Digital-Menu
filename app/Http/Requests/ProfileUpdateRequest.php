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
        return [
            'username'        => ['required', 'string', 'max:50', 'unique:users,username,' . $this->user()->id],
            'whatsapp_number' => ['required', 'string', 'max:20'],
            'email'           => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'name'            => ['nullable', 'string', 'max:255'], // Tambahkan ini (Nama Lapak)
            'shop_name'       => ['nullable', 'string', 'max:255'], // Tambahkan ini
        ];
    }
}