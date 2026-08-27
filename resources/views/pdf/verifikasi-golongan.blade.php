<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi Peserta {{ $event->event_name }}</title>

    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>

    @foreach ($rows as $row)
        @include('public.participant.verifikasi', ['ep' => $row, 'event' => $event, 'group' => $group])

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

</body>

</html>
