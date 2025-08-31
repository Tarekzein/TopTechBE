<?php

namespace Modules\Blog\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'string|max:255|unique:blog_tags,slug',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
        ];

        // If updating, ignore unique rule for current tag
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['slug'] = 'string|max:255|unique:blog_tags,slug,' . $this->route('id');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The tag name is required.',
            'name.max' => 'The tag name cannot exceed 255 characters.',
            'slug.required' => 'The tag slug is required.',
            'slug.unique' => 'This slug is already in use.',
            'slug.max' => 'The tag slug cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'meta_title.max' => 'The meta title cannot exceed 255 characters.',
            'meta_description.max' => 'The meta description cannot exceed 1000 characters.',
        ];
    }
} 