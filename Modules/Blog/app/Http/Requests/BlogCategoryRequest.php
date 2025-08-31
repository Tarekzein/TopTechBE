<?php

namespace Modules\Blog\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class BlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'string|max:255|unique:blog_categories,slug',
            'description' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                Rule::exists('blog_categories', 'id')->whereNull('deleted_at'),
            ],
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
        ];


        // If updating, ignore unique rule for current category
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['slug'] = 'string|max:255|unique:blog_categories,slug,' . $this->route('id');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.max' => 'The category name cannot exceed 255 characters.',
            'slug.required' => 'The category slug is required.',
            'slug.unique' => 'This slug is already in use.',
            'slug.max' => 'The category slug cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'parent_id.exists' => 'The selected parent category does not exist.',
            'order.integer' => 'The order must be a number.',
            'order.min' => 'The order cannot be negative.',
            'meta_title.max' => 'The meta title cannot exceed 255 characters.',
            'meta_description.max' => 'The meta description cannot exceed 1000 characters.',
        ];
    }
} 