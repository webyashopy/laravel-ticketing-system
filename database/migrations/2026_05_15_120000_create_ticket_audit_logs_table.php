<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabulka ticket_audit_logs — append-only log změn ticketů.
 *
 * Zachycuje:
 *   - editace polí ticketu (title, description, category, priority, status)
 *   - lifecycle eventy (close, reopen)
 *   - attachment add/remove (filename v new_value/old_value)
 *   - comment_added/comment_deleted (uuid nebo body v hodnotách)
 *
 * Žádné `updated_at` — append-only. Cascade delete při smazání ticketu.
 * No-op změny (old == new) se nezapisují — handluje TicketAuditService.
 *
 * Idempotentní guard `Schema::hasTable()` — kvůli adopci v hostitelské aplikaci, kde
 * tabulka už existuje.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_audit_logs')) {
            return;
        }

        Schema::create('ticket_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Whitelist polí — viz TicketAuditService konstanty FIELD_*
            $table->string('field', 64);

            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Hot path: detail ticketu načte timeline events (newest first)
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_audit_logs');
    }
};
