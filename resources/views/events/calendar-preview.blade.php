<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar of Events - {{ $event->name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: #525659;
        }

        .sheet {
            width: 100vw;
            height: 100vh;
        }

        .sheet iframe,
        .sheet img {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
            object-fit: contain;
            background: #ffffff;
        }

        @media print {
            html,
            body {
                background: #ffffff;
            }

            .sheet {
                width: 100%;
                height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="sheet">
        @if ($isPdf)
            <iframe src="{{ $url }}" title="Calendar of Events - {{ $event->name }}"></iframe>
        @else
            <img src="{{ $url }}" alt="Calendar of Events - {{ $event->name }}">
        @endif
    </div>
</body>
</html>
