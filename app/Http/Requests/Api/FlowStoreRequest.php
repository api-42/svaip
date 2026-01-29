<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FlowStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            
            // Question cards
            'cards' => 'required|array|min:1',
            'cards.*.type' => 'sometimes|in:question',
            'cards.*.question' => 'required|string|max:255|min:1',
            'cards.*.description' => 'nullable|string|max:255',
            'cards.*.options' => 'required|array|min:2|max:2',
            'cards.*.options.*' => 'required|string|max:255',
            'cards.*.branches' => 'nullable|array',
            'cards.*.branches.*' => 'nullable|integer',
            'cards.*.scoring' => 'nullable|array',
            'cards.*.scoring.*' => 'nullable|integer',
            'cards.*.skipable' => 'sometimes|boolean',
            'cards.*.position' => 'nullable|array',
            'cards.*.position.x' => 'nullable|numeric',
            'cards.*.position.y' => 'nullable|numeric',
            
            // End cards (stored in metadata)
            'end_cards' => 'nullable|array',
            'end_cards.*.type' => 'sometimes|in:end',
            'end_cards.*.message' => 'required|string',
            'end_cards.*.formFields' => 'nullable|array',
            'end_cards.*.formFields.*.label' => 'required|string',
            'end_cards.*.formFields.*.type' => 'required|string|in:text,email,number,tel,date',
            'end_cards.*.formFields.*.required' => 'nullable|boolean',
            'end_cards.*.position' => 'nullable|array',
            'end_cards.*.position.x' => 'nullable|numeric',
            'end_cards.*.position.y' => 'nullable|numeric',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
