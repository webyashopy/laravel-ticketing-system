{{-- Notifikace: změna stavu ticketu — closed/reopened --}}
@component('mail::message')
# Ticket {{ $actionCs }}

Ticket **„{{ $ticket->title }}"** byl {{ $actionCs }} uživatelem **{{ $actor->name }}**.

@if ($action === 'closed')
Pokud považujete vyřešení za nedostatečné, můžete ticket znovu otevřít přes tlačítko **„Reopen"** v detailu.
@else
Ticket je opět ve stavu **Open** a vyžaduje vaši pozornost.
@endif

@component('mail::button', ['url' => rtrim((string) config('app.url'), '/') . $actionUrl])
Otevřít ticket
@endcomponent

---

{{ config('app.name') }}
@endcomponent
