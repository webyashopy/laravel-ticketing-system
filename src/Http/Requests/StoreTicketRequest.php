<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Webyashopy\Tickets\Enums\TicketCategory;
use Webyashopy\Tickets\Enums\TicketPriority;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Validace nového ticketu.
 *
 * Limity (z `config('tickets.*')`):
 *   - max počet příloh:     `max_attachments` (default 20)
 *   - max velikost přílohy: `max_attachment_size_bytes` (default 10 MB)
 *   - povolené přípony:     `allowed_attachment_extensions`
 *     (obrázky: jpg/jpeg/png/webp/gif; dokumenty: pdf; text: txt/log/csv;
 *      office: docx/xlsx; archivy: zip)
 *
 * Žádné SVG/HTML/executable (XSS + RCE vektor — viz config).
 */
class StoreTicketRequest extends FormRequest
{
    /**
     * Autorizace přes TicketPolicy::create().
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Ticket::class);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $maxAttachments = (int) config('tickets.max_attachments', 20);
        // Laravel `max:` u file pravidla bere kilobytes
        $maxFileKb = (int) (((int) config('tickets.max_attachment_size_bytes', 10 * 1024 * 1024)) / 1024);
        $allowedExtensions = (array) config(
            'tickets.allowed_attachment_extensions',
            ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        );
        $mimesRule = 'mimes:' . implode(',', $allowedExtensions);

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],

            // Auto-pole z FE (window.location, viewport, navigator.userAgent)
            'page_url' => ['nullable', 'string', 'max:500'],
            'viewport' => ['nullable', 'string', 'max:32'],
            'user_agent' => ['nullable', 'string', 'max:512'],

            // Přílohy / screenshoty
            'attachments' => ['nullable', 'array', "max:{$maxAttachments}"],
            'attachments.*' => [
                'file',
                "max:{$maxFileKb}",
                $mimesRule,
            ],
        ];
    }

    /**
     * Lokalizované hlášky (česky pro UI).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxAttachments = (int) config('tickets.max_attachments', 20);
        $maxMb = (int) ((int) config('tickets.max_attachment_size_bytes', 10 * 1024 * 1024) / 1024 / 1024);

        return [
            'title.required' => 'Název ticketu je povinný.',
            'title.max' => 'Název ticketu může mít max 255 znaků.',
            'description.required' => 'Popis ticketu je povinný.',
            'description.max' => 'Popis je příliš dlouhý (max 10 000 znaků).',
            'category.required' => 'Vyberte kategorii ticketu.',
            'priority.required' => 'Vyberte prioritu.',
            'attachments.max' => "Maximálně {$maxAttachments} příloh.",
            'attachments.*.max' => "Příloha může mít max {$maxMb} MB.",
            'attachments.*.mimes' => 'Povolené formáty: JPG, JPEG, PNG, WEBP, GIF, '
                . 'PDF, TXT, LOG, CSV, DOCX, XLSX, ZIP.',
        ];
    }
}
