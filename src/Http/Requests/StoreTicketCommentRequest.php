<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Webyashopy\Tickets\Models\TicketComment;

/**
 * Validace pro POST /tickets/{ticket}/comments.
 *
 * Authorize: Gate na TicketCommentPolicy::create (deleguje na ticket view scope).
 */
class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket !== null
            && Gate::allows('create', [TicketComment::class, $ticket]);
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
