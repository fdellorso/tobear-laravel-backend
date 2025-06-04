<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreImageRequest extends FormRequest
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
        $rules = [
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg'],
            'label' => ['nullable', 'string', 'max:255'],
        ];

        $image = $this->all()['image'] ?? false;

        if (!isset($rules['image']) || !is_array($rules['image'])) {
            $rules['image'] = [];
        }

        if (isset($image) && $image instanceof UploadedFile) {
            $rules['image'][] = 'image';
        }

        return $rules;
    }
}
