<?php

namespace Modules\Blog\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'post_id' => 'required|exists:blog_posts,id',
            'parent_id' => 'nullable|exists:blog_comments,id',
            'content' => 'required|string|max:1000',
            'author_name' => 'required|string|max:255',
            'author_email' => 'required|email|max:255',
            'author_url' => 'nullable|url|max:255',
            'status' => 'required|in:pending,approved,spam',
            'user_id' => 'nullable|exists:users,id',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:255',
        ];

        // If updating, make post_id optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['post_id'] = 'nullable|exists:blog_posts,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'post_id.required' => 'The post ID is required.',
            'post_id.exists' => 'The selected post does not exist.',
            'parent_id.exists' => 'The selected parent comment does not exist.',
            'content.required' => 'The comment content is required.',
            'content.max' => 'The comment content cannot exceed 1000 characters.',
            'author_name.required' => 'The author name is required.',
            'author_name.max' => 'The author name cannot exceed 255 characters.',
            'author_email.required' => 'The author email is required.',
            'author_email.email' => 'The author email must be a valid email address.',
            'author_email.max' => 'The author email cannot exceed 255 characters.',
            'author_url.url' => 'The author URL must be a valid URL.',
            'author_url.max' => 'The author URL cannot exceed 255 characters.',
            'status.required' => 'The comment status is required.',
            'status.in' => 'The comment status must be pending, approved, or spam.',
            'user_id.exists' => 'The selected user does not exist.',
            'ip_address.ip' => 'The IP address must be a valid IP address.',
            'user_agent.max' => 'The user agent cannot exceed 255 characters.',
        ];
    }
} 