{{-- Denní digest stale ticketů.
     Oproti původní verzi BEZ sloupce „Organizace" — balíček tenant
     model nezná. --}}
@component('mail::message')
# Stale tickety čekající na akci

V interním ticketingu je **{{ $tickets->count() }} otevřených ticketů**, které jsou starší než {{ $thresholdDays }} dní.

@component('mail::table')
| Vytvořeno | Kategorie | Priorita | Název | Tvůrce |
| --- | --- | --- | --- | --- |
@foreach ($tickets as $ticket)
| {{ $ticket->created_at?->format('d.m.Y') }} | {{ $ticket->category->label() }} | {{ $ticket->priority->label() }} | [{{ \Illuminate\Support\Str::limit($ticket->title, 50) }}]({{ rtrim($appUrl, '/') }}/tickets/{{ $ticket->uuid }}) | {{ $ticket->creator?->name ?? '—' }} |
@endforeach
@endcomponent

Každý odkaz vede na detail ticketu v aplikaci. Po vyřešení ticket zavřete přes tlačítko **"Zavřít"** v detailu.

@component('mail::button', ['url' => rtrim($appUrl, '/') . '/tickets?status=open'])
Zobrazit všechny otevřené tickety
@endcomponent

---

Tento e-mail je automatický denní digest. Každý ticket se pošle pouze jednou (idempotence přes `stale_email_sent_at`). Pokud ticket znovu otevřete (Reopen), `stale_email_sent_at` se vynuluje a po dalších {{ $thresholdDays }} dnech znovu spadne do digestu.

{{ config('app.name') }}
@endcomponent
