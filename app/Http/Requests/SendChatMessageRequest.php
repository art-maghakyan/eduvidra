<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendChatMessageRequest extends FormRequest
{
    /**
     * The route already carries the `verified` middleware, but that middleware
     * is currently a no-op app-wide: App\Models\User does not implement the
     * MustVerifyEmail *contract* (only the trait), and Laravel's
     * EnsureEmailIsVerified short-circuits on `$user instanceof MustVerifyEmail`.
     *
     * The tutor is specified as students-only AND verified-only so every request
     * is attributable to a real person, so the check is enforced here too.
     * Harmless once User implements the contract — the middleware simply runs first.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasVerifiedEmail();
    }

    /**
     * Input is capped here, before it reaches the model — an oversized question
     * is rejected without ever costing tokens.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.max' => 'Keep your question under 2,000 characters.',
        ];
    }
}
