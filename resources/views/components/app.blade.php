@props([
    'title' => 'Games',
])
<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg-1: #0b1020;
            --bg-2: #131a34;
            --panel: #1a2142;
            --ink: #ecf4ff;
            --accent: #57f287;
            --danger: #ff5f6d;
            --warn: #ffd166;
            --blue: #62d0ff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Press Start 2P", monospace;
            background:
                radial-gradient(circle at 20% 20%, #2a3a7a 0%, transparent 35%),
                radial-gradient(circle at 80% 15%, #24536f 0%, transparent 30%),
                linear-gradient(180deg, var(--bg-2), var(--bg-1));
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .panel {
            width: min(960px, 100%);
            background: var(--panel);
            border: 4px solid #ffffff;
            box-shadow: 0 0 0 4px #000000, 0 18px 0 #000000;
            padding: 22px;
            position: relative;
            overflow: hidden;
        }
        .panel::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: repeating-linear-gradient(
                to bottom,
                rgba(255, 255, 255, 0.05) 0,
                rgba(255, 255, 255, 0.05) 2px,
                transparent 2px,
                transparent 6px
            );
            opacity: 0.25;
        }
        .content { position: relative; z-index: 1; }
        a { color: var(--blue); }
    </style>
    @stack('head')
</head>
<body>
    <main class="panel">
        <div class="content">
            {{ $slot }}
        </div>
    </main>
    @stack('scripts')
</body>
</html>
