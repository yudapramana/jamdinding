<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde {{ $event->event_name }}</title>

    <style>
        @page {
            size: A5;
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
        @if ($mode === 'participant')
            @include('public.participant.kokarde', ['ep' => $row])
        @else
            @include('public.participant.kokarde-role', [
                'user' => $row,
                'role' => $role,
                'ep' => null,
            ])
        @endif

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

</body>

</html>
