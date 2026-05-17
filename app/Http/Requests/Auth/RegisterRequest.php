<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|min:10|max:15',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered. Please sign in instead.',
            'password.confirmed' => 'Password and confirm password do not match.',
            'phone.min' => 'Phone number must be at least 10 digits.',
        ];
    }
}
