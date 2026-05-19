<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;
use RuntimeException;

/**
 * Služba pro ukládání příloh ticketu.
 *
 * Per-ticket storage strategie (NE SHA-256 dedup) — screenshoty jsou
 * typicky unikátní. Path:
 *   {disk}/tickets/{ticket_uuid}/{generated_uuid}.{ext}
 *
 * Validace:
 *   - MIME whitelist: viz config('tickets.allowed_attachment_mime_types').
 *     Žádné SVG (XSS vektor přes embedded JS).
 *   - Max velikost: config('tickets.max_attachment_size_bytes').
 *
 * Validace MIME a velikosti probíhá také ve `StoreTicketRequest`,
 * tato služba je druhá obrana (defense in depth).
 */
final class TicketAttachmentStorage
{
    public const BASE_PATH = 'tickets';

    /**
     * Uloží přílohu na disk a vytvoří `TicketAttachment` záznam.
     *
     * @throws InvalidArgumentException Pokud MIME / velikost neodpovídá whitelistu.
     * @throws RuntimeException         Pokud zápis na disk selže.
     */
    public function store(UploadedFile $file, Ticket $ticket): TicketAttachment
    {
        $this->assertAllowedMimeType($file);
        $this->assertAllowedSize($file);

        $diskName = $this->diskName();
        $disk = Storage::disk($diskName);

        // Cílová cesta: tickets/{ticket_uuid}/{attachment_uuid}.{ext}
        $attachmentUuid = (string) Str::uuid();
        $extension = $this->resolveExtension($file);
        $relativePath = sprintf(
            '%s/%s/%s.%s',
            self::BASE_PATH,
            $ticket->uuid,
            $attachmentUuid,
            $extension,
        );

        // putFileAs zachová bezpečné jméno (UUID + ext). Soubor je už
        // validovaný — extension přes resolveExtension() je whitelist-only.
        $stored = $disk->putFileAs(
            sprintf('%s/%s', self::BASE_PATH, $ticket->uuid),
            $file,
            sprintf('%s.%s', $attachmentUuid, $extension),
        );

        if ($stored === false) {
            throw new RuntimeException(sprintf(
                'Nepodařilo se uložit přílohu ticketu %s na disk %s',
                $ticket->uuid,
                $diskName,
            ));
        }

        return TicketAttachment::create([
            'uuid' => $attachmentUuid,
            'ticket_id' => $ticket->id,
            'filename' => $file->getClientOriginalName(),
            'stored_path' => $relativePath,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
        ]);
    }

    /**
     * Smaže přílohu z disku i z DB.
     *
     * Idempotentní — pokud soubor neexistuje, jen smaže DB záznam.
     */
    public function delete(TicketAttachment $attachment): void
    {
        $disk = Storage::disk($this->diskName());

        if ($disk->exists($attachment->stored_path)) {
            $disk->delete($attachment->stored_path);
        }

        $attachment->delete();
    }

    /**
     * Validace MIME proti whitelistu z configu.
     *
     * `application/octet-stream` je povolen POUZE pro extensions
     * v `octet_stream_fallback_extensions` (txt/log/csv) — PHP guessing
     * pro plain-text soubory často vrátí octet-stream místo text/plain.
     * Pro ostatní MIME platí strict whitelist (žádný octet-stream pro
     * neznámé / executable přípony).
     */
    private function assertAllowedMimeType(UploadedFile $file): void
    {
        $allowed = (array) config('tickets.allowed_attachment_mime_types', []);
        $mime = $file->getMimeType();

        if ($mime === null || ! in_array($mime, $allowed, true)) {
            throw new InvalidArgumentException(sprintf(
                'Nepovolený MIME typ přílohy: %s. Povoleno: %s',
                $mime ?? '(neznámý)',
                implode(', ', $allowed),
            ));
        }

        // Anti-spoofing: octet-stream smí jen pro povolené plain-text přípony.
        if ($mime === 'application/octet-stream') {
            $extension = strtolower($file->getClientOriginalExtension());
            $fallbackExts = (array) config('tickets.octet_stream_fallback_extensions', []);

            if (! in_array($extension, $fallbackExts, true)) {
                throw new InvalidArgumentException(sprintf(
                    'MIME application/octet-stream je povolen pouze pro přípony: %s. Předáno: .%s',
                    implode(', ', $fallbackExts),
                    $extension,
                ));
            }
        }
    }

    /**
     * Validace velikosti proti configu.
     */
    private function assertAllowedSize(UploadedFile $file): void
    {
        $max = (int) config('tickets.max_attachment_size_bytes', 10 * 1024 * 1024);
        $size = (int) $file->getSize();

        if ($size <= 0 || $size > $max) {
            throw new InvalidArgumentException(sprintf(
                'Velikost přílohy (%d B) překračuje limit %d B.',
                $size,
                $max,
            ));
        }
    }

    /**
     * Vrátí povolenou příponu — fallback z MIME, ne z user inputu.
     * Tím eliminuje "fake .png with PHP content" útok.
     */
    private function resolveExtension(UploadedFile $file): string
    {
        $allowed = (array) config('tickets.allowed_attachment_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

        // Preferuj guessExtension (z MIME magic bytes), pak fallback na původní příponu.
        $ext = strtolower((string) $file->guessExtension());

        if ($ext === '' || ! in_array($ext, $allowed, true)) {
            $ext = strtolower($file->getClientOriginalExtension());
        }

        if (! in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException(sprintf(
                'Nepovolená přípona souboru: %s',
                $ext,
            ));
        }

        return $ext;
    }

    /**
     * Disk z configu (default 'local' = storage/app).
     */
    private function diskName(): string
    {
        return (string) config('tickets.storage_disk', 'local');
    }
}
