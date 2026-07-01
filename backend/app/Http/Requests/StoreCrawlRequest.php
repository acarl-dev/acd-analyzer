<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCrawlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string'],
            'maxPages' => ['nullable', 'integer', 'min:1', 'max:25'],
            'maxDepth' => ['nullable', 'integer', 'min:0', 'max:2'],
        ];
    }

    public function url(): string
    {
        return $this->string('url')->toString();
    }

    public function maxPages(): int
    {
        return $this->integer('maxPages', 10);
    }

    public function maxDepth(): int
    {
        return $this->integer('maxDepth', 1);
    }
}