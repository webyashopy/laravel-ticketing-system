<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validace přidání jedné přílohy k existujícímu ticketu
 *.
 *
 * Reuse stejných limitů a whitelistu jako `StoreTicketRequest::attachments.*`:
 *   - max velikost přílohy: `tickets.max_attachment_size_bytes` (default 10 MB)
 *   - povolené přípony:     `tickets.allowed_attachment_extensions`
 *
 * Per-ticket limit (max počet příloh) se kontroluje v controlleru —
 * FormRequest neumí elegantně dotázat existující count.
 *
 * Autorizace přes TicketPolicy::update() — deleguje na `TicketAuthorizer`.
 */
class AddTicketAttachmentRequest extends FormRequest
{
    /**
     * Reuse `update` Policy.
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
        // Laravel `max:` u file pravidla bere kilobytes
        $maxFileKb = (int) (((int) config('tickets.max_attachment_size_bytes', 10 * 1024 * 1024)) / 1024);
        $allowedExtensions = (array) config(
            'tickets.allowed_attachment_extensions',
            ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        );
        $mimesRule = 'mimes:' . implode(',', $allowedExtensions);

        return [
            'file' => [
                'required',
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
        $maxMb = (int) ((int) config('tickets.max_attachment_size_bytes', 10 * 1024 * 1024) / 1024 / 1024);

        return [
            'file.required' => 'Soubor je povinný.',
            'file.file' => 'Nahraný objekt není platný soubor.',
            'file.max' => "Příloha může mít max {$maxMb} MB.",
            'file.mimes' => 'Povolené formáty: JPG, JPEG, PNG, WEBP, GIF, '
                . 'PDF, TXT, LOG, CSV, DOCX, XLSX, ZIP.',
        ];
    }
}
