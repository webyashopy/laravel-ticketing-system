<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validace pro PATCH /comments/{comment}.
 *
 * Authorize: TicketCommentPolicy::update — jen autor, do 5 min po vytvoření.
 */
class UpdateTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        return $comment !== null
            && Gate::allows('update', $comment);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Komentář nesmí být prázdný.',
            'body.min' => 'Komentář nesmí být prázdný.',
            'body.max' => 'Komentář nesmí být delší než 5000 znaků.',
        ];
    }
}
