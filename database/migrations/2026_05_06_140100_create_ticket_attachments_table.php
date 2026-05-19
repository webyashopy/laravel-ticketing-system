<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabulka ticket_attachments — screenshoty / přílohy ticketu.
 *
 * Per-ticket storage strategy (NE SHA-256 dedup) — screenshoty jsou typicky
 * unikátní, dedup nepřináší v MVP benefit. Path:
 *   tickets/{ticket_uuid}/{attachment_uuid}.{ext}
 *
 * Mime whitelist (validace ve FormRequest + service) — viz config
 * `tickets.allowed_attachment_mime_types`. NIKDY SVG — XSS vektor.
 *
 * Idempotentní guard `Schema::hasTable()` — kvůli adopci v hostitelské aplikaci, kde
 * tabulka už existuje.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_attachments')) {
            return;
        }

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            // Originální název souboru (zobrazuje se v UI)
            $table->string('filename', 255);

            // Cesta v storage disku (relative, např. "tickets/{uuid}/{att-uuid}.png")
            $table->string('stored_path', 500);

            $table->string('mime_type', 64);
            $table->unsignedInteger('size_bytes');

            $table->timestamps();

            // Eager-load při show() detailu ticketu
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
