<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust if you need auth
    }

    public function rules(): array
    {
        $existingNames = $this->existingNames();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::notIn($existingNames),
            ],
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Collect existing game names to prevent duplicates.
     */
    private function existingNames(): array
    {
        $custom = $this->session()->get('custom_games', []);
        $customNames = array_map(fn ($g) => $g['name'] ?? '', $custom);

        // Known defaults; expand here when adding more built‑in games.
        $defaultNames = ['Word Quest'];

        return array_filter(array_unique(array_merge($defaultNames, $customNames)));
    }
}
