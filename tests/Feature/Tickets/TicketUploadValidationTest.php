<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Validační pravidla pro nahrávání příloh.
 *
 * Limity (config/tickets.php):
 *   - max počet příloh:  20
 *   - max velikost:      10 MB (10240 KB)
 *   - whitelist přípon:  jpg/jpeg/png/webp/gif (obrázky),
 *                        pdf, txt/log/csv, docx/xlsx, zip
 *                        (NIKDY svg/html/exe/php — XSS/RCE)
 */
class TicketUploadValidationTest extends BaseTicketTest
{
    public function test_dvacaty_prvni_soubor_je_odmitnut(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        // 21 souborů (limit je 20)
        $files = [];
        for ($i = 1; $i <= 21; $i++) {
            $files[] = UploadedFile::fake()->image("shot-{$i}.png", 100, 100);
        }

        $response = $this->post('/tickets', [
            'title' => 'Hodně screenshotů',
            'description' => 'Přibalil jsem 21 souborů.',
            'category' => 'bug',
            'priority' => 'medium',
            'attachments' => $files,
        ]);

        $response->assertSessionHasErrors(['attachments']);
        // Žádný ticket nesmí vzniknout
        $this->assertSame(0, Ticket::count());
    }

    public function test_dvacet_souboru_je_prijato(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $files = [];
        for ($i = 1; $i <= 20; $i++) {
            $files[] = UploadedFile::fake()->image("shot-{$i}.png", 100, 100);
        }

        $response = $this->post('/tickets', [
            'title' => 'Maximum příloh',
            'description' => 'Přesně 20 souborů.',
            'category' => 'bug',
            'priority' => 'medium',
            'attachments' => $files,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
        $this->assertSame(20, Ticket::first()->attachments()->count());
    }

    public function test_soubor_vetsi_nez_10mb_je_odmitnut(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        // 11 MB > 10 MB limit
        $bigFile = UploadedFile::fake()->create('big.png', 11 * 1024, 'image/png');

        $response = $this->post('/tickets', [
            'title' => 'Velký screenshot',
            'description' => 'Tahle příloha je moc velká.',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$bigFile],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, Ticket::count());
    }

    public function test_svg_je_odmitnut_kvuli_xss_riziku(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $svg = UploadedFile::fake()->createWithContent(
            'malicious.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script></svg>',
        );

        $response = $this->post('/tickets', [
            'title' => 'SVG attack',
            'description' => 'Posílám SVG',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$svg],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, Ticket::count());
    }

    public function test_html_je_odmitnut_kvuli_xss(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $html = UploadedFile::fake()->createWithContent(
            'page.html',
            '<html><body><script>alert(1)</script></body></html>',
        );

        $response = $this->post('/tickets', [
            'title' => 'HTML',
            'description' => 'Posílám HTML',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$html],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, Ticket::count());
    }

    public function test_exe_je_odmitnut_kvuli_rce(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $exe = UploadedFile::fake()->createWithContent('virus.exe', "MZ\x00\x00fake binary");

        $response = $this->post('/tickets', [
            'title' => 'EXE',
            'description' => 'Posílám binárku',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$exe],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, Ticket::count());
    }

    public function test_php_je_odmitnut(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $php = UploadedFile::fake()->createWithContent('shell.php', '<?php phpinfo();');

        $response = $this->post('/tickets', [
            'title' => 'PHP shell',
            'description' => 'Posílám PHP',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$php],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, Ticket::count());
    }

    public function test_pdf_priloha_je_prijata(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        // %PDF- header zajistí že mime detekce vrátí application/pdf
        $pdf = UploadedFile::fake()->createWithContent(
            'report.pdf',
            "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n",
        );

        $response = $this->post('/tickets', [
            'title' => 'PDF report',
            'description' => 'Přikládám PDF',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$pdf],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
        $attachment = Ticket::first()->attachments()->first();
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertStringEndsWith('.pdf', $attachment->stored_path);
    }

    public function test_txt_priloha_je_prijata(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $txt = UploadedFile::fake()->createWithContent(
            'console.txt',
            "Error: Cannot read properties of undefined\n  at App.tsx:42\n",
        );

        $response = $this->post('/tickets', [
            'title' => 'Console log',
            'description' => 'TXT z konzole',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$txt],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, Ticket::first()->attachments()->count());
    }

    public function test_log_priloha_je_prijata(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $log = UploadedFile::fake()->createWithContent(
            'server.log',
            "[2026-05-15 10:00:00] production.ERROR: SQLSTATE[HY000]\n",
        );

        $response = $this->post('/tickets', [
            'title' => 'Server log',
            'description' => 'LOG soubor',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$log],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
    }

    public function test_csv_priloha_je_prijata(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $csv = UploadedFile::fake()->createWithContent(
            'export.csv',
            "id,name,email\n1,Jan Novák,jan@example.com\n2,Petra,p@example.com\n",
        );

        $response = $this->post('/tickets', [
            'title' => 'CSV export',
            'description' => 'CSV dat',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$csv],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
    }

    public function test_zip_priloha_je_prijata(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        // Reálný ZIP s 1 souborem (vytvoření přes ZipArchive zajistí správný MIME)
        $tmpPath = tempnam(sys_get_temp_dir(), 'ticket-test-') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($tmpPath, \ZipArchive::CREATE);
        $zip->addFromString('inside.txt', 'obsah uvnitř archivu');
        $zip->close();

        $zipFile = new UploadedFile(
            $tmpPath,
            'bundle.zip',
            'application/zip',
            null,
            true, // testovací režim — nemaže source
        );

        $response = $this->post('/tickets', [
            'title' => 'ZIP archiv',
            'description' => 'Posílám balík',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$zipFile],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());

        @unlink($tmpPath);
    }

    public function test_docx_priloha_je_prijata(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        // DOCX = ZIP archiv se specifickou strukturou.
        $tmpPath = tempnam(sys_get_temp_dir(), 'ticket-test-') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tmpPath, \ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types/>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><document/>');
        $zip->close();

        $docx = new UploadedFile(
            $tmpPath,
            'report.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );

        $response = $this->post('/tickets', [
            'title' => 'DOCX report',
            'description' => 'Posílám Word',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$docx],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());

        @unlink($tmpPath);
    }

    public function test_xlsx_priloha_je_prijata(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $tmpPath = tempnam(sys_get_temp_dir(), 'ticket-test-') . '.xlsx';
        $zip = new \ZipArchive();
        $zip->open($tmpPath, \ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types/>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook/>');
        $zip->close();

        $xlsx = new UploadedFile(
            $tmpPath,
            'data.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->post('/tickets', [
            'title' => 'XLSX data',
            'description' => 'Posílám Excel',
            'category' => 'bug',
            'priority' => 'low',
            'attachments' => [$xlsx],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());

        @unlink($tmpPath);
    }

    public function test_kombinace_typu_v_jednom_ticketu(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $png = UploadedFile::fake()->image('shot.png', 100, 100);
        $pdf = UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\n%%EOF\n");
        $txt = UploadedFile::fake()->createWithContent('log.txt', 'error trace');

        $response = $this->post('/tickets', [
            'title' => 'Multi-typ',
            'description' => 'PNG + PDF + TXT v jednom ticketu',
            'category' => 'bug',
            'priority' => 'medium',
            'attachments' => [$png, $pdf, $txt],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
        $this->assertSame(3, Ticket::first()->attachments()->count());
    }

    public function test_validace_required_poli(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/tickets', []);

        $response->assertSessionHasErrors(['title', 'description', 'category', 'priority']);
    }

    public function test_neplatna_kategorie_je_odmitnuta(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/tickets', [
            'title' => 'Test',
            'description' => 'Test',
            'category' => 'invalid_category', // mimo enum
            'priority' => 'low',
        ]);

        $response->assertSessionHasErrors(['category']);
    }
}
