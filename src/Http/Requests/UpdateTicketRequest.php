<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Webyashopy\Tickets\Enums\TicketCategory;
use Webyashopy\Tickets\Enums\TicketPriority;

/**
 * Validace edit ticketu — partial update.
 *
 * Editovatelná pole: title, description, category, priority.
 * Všechna pole jsou `sometimes` — FE inline edit pošle jen změněné pole,
 * ne kompletní entitu.
 *
 * Attachmenty se NEspravují tímto endpointem — mají separátní routes.
 *
 * Authorize: TicketPolicy::update — deleguje na `TicketAuthorizer::canManage()`
 * (route-model binding zajistí 404 pro neexistující UUID).
 */
class UpdateTicketRequest extends FormRequest
{
    /**
     * Autorizace přes TicketPolicy::update($ticket).
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('ticket'));
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'category' => ['sometimes', 'required', Rule::enum(TicketCategory::class)],
            'priority' => ['sometimes', 'required', Rule::enum(TicketPriority::class)],
        ];
    }

    /**
     * Lokalizované hlášky.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Název ticketu je povinný.',
            'title.string' => 'Název musí být text.',
            'title.max' => 'Název ticketu může mít max 255 znaků.',
            'description.required' => 'Popis ticketu je povinný.',
            'description.string' => 'Popis musí být text.',
            'description.max' => 'Popis je příliš dlouhý (max 10 000 znaků).',
            'category.required' => 'Kategorie je povinná.',
            'category.enum' => 'Neplatná kategorie ticketu.',
            'priority.required' => 'Priorita je povinná.',
            'priority.enum' => 'Neplatná priorita ticketu.',
        ];
    }
}
