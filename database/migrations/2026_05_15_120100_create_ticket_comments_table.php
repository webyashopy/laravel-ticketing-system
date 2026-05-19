<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabulka ticket_comments — lineární komentáře pod ticketem.
 *
 * Bez threadingu (parent_id), bez soft deletes (audit log řeší historii),
 * bez internal_note flagu — lineární diskuze.
 *
 * Body je raw Markdown (max 5000 znaků — validace v FormRequestu),
 * sanitization probíhá v `TicketComment::body_html` accessoru.
 *
 * Cascade delete při smazání ticketu, user_id null on delete (zachování
 * historie i po smazání usera).
 *
 * Idempotentní guard `Schema::hasTable()` — kvůli adopci v hostitelské aplikaci, kde
 * tabulka už existuje.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_comments')) {
            return;
        }

        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Raw Markdown — sanitization při render přes body_html accessor
            $table->text('body');

            $table->timestamps();

            // Hot path: detail ticketu načte komentáře chronologicky
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
