<?php

namespace Modules\Blog\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => 'string|max:255|unique:blog_posts,slug,' . ($this->post ?? ''),
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Updated for file upload
            'category_id' => 'required|exists:blog_categories,id',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'is_featured' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:blog_tags,id'
        ];

        if ($this->isMethod('POST')) {
            $rules['slug'] = 'string|max:255|unique:blog_posts,slug';
        }

        // For updates, make featured_image optional if it's not being changed
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['featured_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The post title is required.',
            'title.max' => 'The post title cannot exceed 255 characters.',
            'slug.required' => 'The post slug is required.',
            'slug.unique' => 'This slug is already in use.',
            'content.required' => 'The post content is required.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'status.required' => 'Please select a status.',
            'status.in' => 'The selected status is invalid.',
            'published_at.date' => 'The published date must be a valid date.',
            'featured_image.image' => 'The featured image must be an image file.',
            'featured_image.mimes' => 'The featured image must be a file of type: jpeg, png, jpg, gif, webp.',
            'featured_image.max' => 'The featured image may not be greater than 5MB.',
            'tags.*.exists' => 'One or more selected tags are invalid.'
        ];
    }
}