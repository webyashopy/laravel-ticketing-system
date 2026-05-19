{{-- Notifikace: editace ticketu — změna polí --}}
@component('mail::message')
# Ticket upraven

Uživatel **{{ $actor->name }}** upravil ticket **„{{ $ticket->title }}"**.

## Seznam změn

@component('mail::table')
| Pole | Původní hodnota | Nová hodnota |
| --- | --- | --- |
@foreach ($changedFields as $change)
| {{ $change['field'] ?? '—' }} | {{ $change['old'] ?? '—' }} | {{ $change['new'] ?? '—' }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => rtrim((string) config('app.url'), '/') . $actionUrl])
Otevřít ticket
@endcomponent

---

{{ config('app.name') }}
@endcomponent
