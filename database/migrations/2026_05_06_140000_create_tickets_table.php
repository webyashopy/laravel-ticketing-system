<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabulka tickets — interní bug-tracker / ticketing nástroj.
 *
 * Lifecycle: Open → Closed (jednoduché, žádné mezistavy v MVP).
 * Visibility: per-tenant — sloupec `tenant_id` je
 * generický nullable `unsignedBigInteger` BEZ FK; balíček tenant model
 * nezná, izolaci dat řeší kontrakt TicketTenantResolver. Single-tenant
 * projekt nechává `tenant_id` NULL.
 *
 * Sloupce `closed_at` + `closed_by_user_id` slouží jako audit (žádné
 * soft delete, ticket se jen zavírá / znovu otevírá).
 *
 * Sloupec `stale_email_sent_at` zajišťuje idempotenci denního cron
 * notifikátoru — viz TicketStaleNotifier.
 *
 * Idempotentní guard `Schema::hasTable()` — kvůli adopci v hostitelské aplikaci, kde
 * tabulka už existuje.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Tenant scope — generický nullable
            // identifikátor BEZ FK. Balíček tenant model nezná.
            $table->unsignedBigInteger('tenant_id')->nullable();

            // Tvůrce ticketu (FK na users — společná tabulka napříč projekty)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description');

            // Enums (string-backed, viz Webyashopy\Tickets\Enums\Ticket*).
            // Validujeme přes FormRequest + cast v modelu.
            $table->string('category', 16);          // bug | feature | question | other
            $table->string('priority', 16);          // low | medium | high | urgent
            $table->string('status', 16)->default('open'); // open | closed

            // Auto-pole z FE (kontext bug reportu)
            $table->string('page_url', 500)->nullable();
            $table->string('viewport', 32)->nullable();
            $table->string('user_agent', 512)->nullable();

            // Audit zavření / znovu otevření
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Idempotence stale-notification cronu (TicketStaleNotifier)
            $table->timestamp('stale_email_sent_at')->nullable();

            $table->timestamps();

            // Hlavní list query: filtr per tenant + status + řazení podle data
            $table->index(['tenant_id', 'status', 'created_at']);
            // Badge "moje otevřené tickety" v sidebaru
            $table->index(['user_id', 'status']);
            // Cron filtr: WHERE stale_email_sent_at IS NULL AND created_at < now() - 3 days
            $table->index('stale_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
