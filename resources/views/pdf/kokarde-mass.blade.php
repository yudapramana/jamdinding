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

        .page-break {
            page-break-after: always;
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

        <div style="page-break-after: always;"></div>
    @endforeach


</body>

</html>
