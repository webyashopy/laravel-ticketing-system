<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webyashopy\Tickets\TicketsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Service providery balíčku načtené do testovací aplikace.
     *
     * `Inertia\ServiceProvider` přidáváme explicitně — Testbench
     * auto-discoveruje providery jen z balíčku „under test", ne z jeho
     * runtime závislostí. Bez něj se neregistrují Inertia testing macra
     * (`assertInertia`) pro feature testy HTTP vrstvy.
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            TicketsServiceProvider::class,
        ];
    }

    /**
     * Testovací prostředí — in-memory SQLite.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Migrace pro testovací DB.
     *
     * Balíčkové migrace `tickets`/`ticket_*` mají FK na tabulku `users`,
     * kterou v reálném projektu dodává host aplikace. V izolovaném
     * Testbench prostředí ji proto musíme vytvořit jako minimální stub
     * PŘED tím, než se spustí balíčkové migrace (`loadMigrationsFrom`).
     */
    protected function defineDatabaseMigrations(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }
    }
}
