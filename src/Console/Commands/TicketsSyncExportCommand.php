<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Enums\TicketStatus;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Services\TicketMarkdownExporter;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Export ticketů jako markdown do git repa pro lokální skill `buguj`
 *.
 *
 * Cílová cesta: `config('tickets.sync_export_path')` (env
 * `TICKETS_SYNC_EXPORT_PATH`). Musí to být git repo. Bez vyplněné /
 * neexistující cesty se příkaz tiše přeskočí (SUCCESS) — scheduling
 * v `TicketsServiceProvider` ho navíc registruje JEN pokud je cesta vyplněna.
 *
 * Struktura repa (čte ji skill `buguj`):
 *   open/<id>-<slug>.md     — otevřené tickety
 *   closed/<id>-<slug>.md   — zavřené tickety
 *
 * Idempotence: oba adresáře se před exportem vyčistí a přepíšou. Markdown
 * obsah generuje sdílený {@see TicketMarkdownExporter} (stejný formát jako
 * `GET /api/tickets/{uuid}/export.md`). Po zápisu proběhne `git add/commit/push`
 * — pokud nejsou žádné změny, commit se přeskočí.
 */
class TicketsSyncExportCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tickets:sync-export';

    /**
     * @var string
     */
    protected $description = 'Exportuje tickety jako markdown do git repa pro skill buguj (config tickets.sync_export_path)';

    public function handle(TicketMarkdownExporter $exporter): int
    {
        $path = config('tickets.sync_export_path');

        // Bez nakonfigurované / existující cesty se export tiše přeskočí.
        if (! is_string($path) || $path === '' || ! is_dir($path)) {
            $this->info('tickets.sync_export_path není nastaven nebo cesta neexistuje — export přeskočen.');

            return self::SUCCESS;
        }

        try {
            $counts = $this->exportTickets($exporter, $path);
            $this->commitAndPush($path, $counts);
        } catch (Throwable $e) {
            $this->error('Sync export selhal: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Export hotov: %d otevřených, %d zavřených ticketů.',
            $counts['open'],
            $counts['closed'],
        ));

        return self::SUCCESS;
    }

    /**
     * Vyčistí cílové adresáře a zapíše markdown soubory ticketů.
     *
     * @return array{open: int, closed: int} Počty exportovaných ticketů.
     */
    private function exportTickets(TicketMarkdownExporter $exporter, string $basePath): array
    {
        $counts = ['open' => 0, 'closed' => 0];

        foreach (['open', 'closed'] as $bucket) {
            $dir = $basePath . DIRECTORY_SEPARATOR . $bucket;

            // Vyčistit adresář — markdown soubory přepisujeme kompletně.
            if (is_dir($dir)) {
                foreach (glob($dir . DIRECTORY_SEPARATOR . '*.md') ?: [] as $file) {
                    @unlink($file);
                }
            } else {
                @mkdir($dir, 0775, true);
            }
        }

        // Pro stabilní názvy souborů použijeme auto-increment id (ne UUID).
        Ticket::query()
            ->orderBy('id')
            ->with(['creator', 'closer', 'attachments'])
            ->chunk(100, function ($tickets) use ($exporter, $basePath, &$counts): void {
                foreach ($tickets as $ticket) {
                    $bucket = $ticket->status === TicketStatus::CLOSED ? 'closed' : 'open';
                    $filename = sprintf(
                        '%d-%s.md',
                        $ticket->id,
                        $this->slug($ticket->title),
                    );
                    $target = $basePath . DIRECTORY_SEPARATOR . $bucket . DIRECTORY_SEPARATOR . $filename;

                    file_put_contents($target, $exporter->export($ticket));
                    $counts[$bucket]++;
                }
            });

        return $counts;
    }

    /**
     * Bezpečný slug z titulku ticketu pro název souboru.
     */
    private function slug(string $title): string
    {
        $slug = Str::slug($title);

        return $slug !== '' ? Str::limit($slug, 60, '') : 'ticket';
    }

    /**
     * `git add/commit/push` v cílovém repu. Pokud nejsou změny, commit se
     * přeskočí (git vrátí nenulový kód → tolerujeme).
     *
     * @param  array{open: int, closed: int}  $counts
     */
    private function commitAndPush(string $path, array $counts): void
    {
        $this->runGit($path, ['add', '-A']);

        // `git diff --cached --quiet` → exit 0 = žádné změny, exit 1 = změny.
        $diff = new Process(['git', 'diff', '--cached', '--quiet'], $path);
        $diff->run();

        if ($diff->getExitCode() === 0) {
            $this->info('Žádné změny ticketů — commit přeskočen.');

            return;
        }

        $message = sprintf(
            'sync: tickety %s (%d open, %d closed)',
            now()->format('Y-m-d H:i'),
            $counts['open'],
            $counts['closed'],
        );

        $this->runGit($path, ['commit', '-m', $message]);
        $this->runGit($path, ['push']);
    }

    /**
     * Spustí git příkaz v daném repu, vyhodí výjimku při selhání.
     *
     * @param  array<int, string>  $args
     */
    private function runGit(string $cwd, array $args): void
    {
        $process = new Process(['git', ...$args], $cwd);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'git %s selhalo: %s',
                implode(' ', $args),
                trim($process->getErrorOutput() ?: $process->getOutput()),
            ));
        }
    }
}
