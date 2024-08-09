<?php

namespace App\Http\Requests\user\user;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'user_id' => [
                'required',
                'exists:users,id'
            ],
            'name' => 'required|string',
            'address' => 'required|string',
            'username' => [
                'required',
                'string',
                Rule::unique('users', 'username')->ignore($this->user_id),
            ],
            'phone_number' => [
                'required',
                'string',
                Rule::unique('users', 'phone_number')->ignore($this->user_id),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($this->user_id),
            ],
        ];
    }
}
