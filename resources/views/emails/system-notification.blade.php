<x-mail::message>
# {{ $title }}

@if($userName !== '')
Здравствуйте, **{{ $userName }}**!
@endif

{{ $message }}

@if($actionUrl !== '')
<x-mail::button :url="$actionUrl">
Открыть в системе
</x-mail::button>
@endif

С уважением,<br>
{{ config('mail.from.name', config('app.name')) }}
</x-mail::message>
