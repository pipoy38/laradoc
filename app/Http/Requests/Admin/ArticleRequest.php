<?php
namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('title', '')), 'noindex' => $this->boolean('noindex'), 'og_image_id' => $this->input('og_image_id') ?: null]);
    }

    public function rules(): array
    {
        $id = $this->route('article')?->id;
        return [
            'title' => 'required|string|max:200',
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9-]+$/', Rule::unique('articles')->where('category_id', $this->input('category_id'))->ignore($id),
                function ($attr, $value, $fail) {
                    if (Category::where('parent_id', $this->input('category_id'))->where('slug', $value)->exists()) {
                        $fail('A sub-category already uses that slug.');
                    }
                }],
            'category_id' => 'required|integer|exists:categories,id',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'body_html' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'tags' => 'nullable|string|max:500',
            'change_note' => 'nullable|string|max:200',
            'seo_title' => 'nullable|string|max:120',
            'meta_description' => 'nullable|string|max:320',
            'canonical_url' => 'nullable|url|max:255',
            'noindex' => 'boolean',
            'og_image_id' => 'nullable|integer|exists:media,id',
        ];
    }
}
