<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Support\AdminPrefix;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('name', '')), 'parent_id' => $this->input('parent_id') ?: null]);
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'name' => 'required|string|max:120',
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('categories')->where('parent_id', $this->input('parent_id'))->ignore($id),
                function ($attr, $value, $fail) {
                    if ($this->input('parent_id') === null && in_array($value, [...AdminPrefix::reserved(), AdminPrefix::current()], true)) {
                        $fail('That slug is reserved.');
                    }
                }],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id',
                function ($attr, $value, $fail) use ($id) {
                    if ($id && Category::find($id)?->isOrContains((int) $value)) {
                        $fail('A category cannot be inside itself.');
                    }
                }],
            'description' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:32',
        ];
    }
}
