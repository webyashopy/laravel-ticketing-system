{{-- Notifikace: nový komentář u ticketu --}}
@component('mail::message')
# Nový komentář u ticketu

**{{ $actor->name }}** přidal komentář k ticketu **„{{ $ticket->title }}"**.

@component('mail::panel')
{{ $comment->body }}
@endcomponent

@component('mail::button', ['url' => rtrim((string) config('app.url'), '/') . $actionUrl])
Otevřít ticket
@endcomponent

---

Tento e-mail byl odeslán z {{ config('app.name') }}. Pokud nechcete dostávat upozornění na komentáře, kontaktujte správce.
@endcomponent
