<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Project Manager') }} — @yield('title', 'Painel')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            --bg:        #0d1117;
            --surface:   #161e2d;
            --surface-2: #1c2535;
            --border:    #253047;
            --border-2:  #2a3a52;
            --border-3:  #354865;
            --muted-3:   #4e6080;
            --muted-2:   #607090;
            --muted-1:   #7890aa;
            --text:      #c9d8ee;
        }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; background: var(--bg); color: var(--text); }

        /* Sidebar */
        .sidebar-link { transition: all .15s ease; }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(99,102,241,.2) 0%, rgba(99,102,241,.06) 100%);
            border-left: 2px solid #6366f1;
            padding-left: calc(.75rem - 2px);
            color: #a5b4fc;
        }
        .sidebar-link:not(.active):hover { background: rgba(255,255,255,.05); color: #e2e8f0; }

        /* Cards */
        .card-hover { transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,.35);
            border-color: rgba(99,102,241,.35) !important;
        }

        /* Misc */
        [x-cloak] { display: none !important; }
        .toast-enter { animation: toastIn .3s ease forwards; }
        @keyframes toastIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

        /* Markdown */
        .prose-pm h1,.prose-pm h2,.prose-pm h3 { color:#e2e8f0; font-weight:600; margin-top:1.25em; margin-bottom:.5em; }
        .prose-pm p { color:#94a3b8; margin-bottom:.75em; line-height:1.7; }
        .prose-pm code { background:rgba(99,102,241,.15); color:#a5b4fc; padding:.15em .4em; border-radius:.25em; font-size:.875em; }
        .prose-pm pre { background:var(--surface-2); border:1px solid var(--border); border-radius:.5em; padding:1em; overflow-x:auto; }
        .prose-pm pre code { background:none; color:#94a3b8; padding:0; }
        .prose-pm ul,.prose-pm ol { color:#94a3b8; padding-left:1.5em; margin-bottom:.75em; }
        .prose-pm li { margin-bottom:.25em; }
        .prose-pm strong { color:#e2e8f0; }
        .prose-pm a { color:#818cf8; text-decoration:underline; }
        .prose-pm blockquote { border-left:3px solid #4338ca; padding-left:1em; color:var(--muted-1); font-style:italic; }

        /* Range input */
        input[type=range] { -webkit-appearance:none; height:4px; background:var(--border); border-radius:9999px; outline:none; }
        input[type=range]::-webkit-slider-thumb { -webkit-appearance:none; width:16px; height:16px; border-radius:50%; background:#6366f1; cursor:pointer; box-shadow:0 0 0 3px rgba(99,102,241,.25); }

        /* Scrollbar */
        ::-webkit-scrollbar { width:5px; height:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:var(--border); border-radius:9999px; }
        ::-webkit-scrollbar-thumb:hover { background:var(--border-2); }

        /* Forms */
        select option { background: var(--surface-2); }
        input:focus, textarea:focus, select:focus { box-shadow: 0 0 0 3px rgba(99,102,241,.18); }
    </style>
</head>
<body class="h-full antialiased">
<div class="flex h-full">

    {{-- ── Sidebar ────────────────────────────────────────── --}}
    <aside class="w-64 flex flex-col fixed inset-y-0 z-20"
        style="background:#141c2b; border-right:1px solid var(--border);">

        {{-- Logo --}}
        <div class="px-5 py-5" style="border-bottom:1px solid var(--border);">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                    style="background:linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow:0 4px 12px rgba(99,102,241,.35);">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 10h16M4 14h10"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-white leading-tight">Project Manager</div>
                    <div class="text-xs" style="color:var(--muted-2);">workspace</div>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            <p class="text-xs font-semibold px-3 mb-2" style="color:var(--muted-3); letter-spacing:.08em;">MENU</p>

            <a href="{{ route('dashboard') }}"
                class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                style="{{ !request()->routeIs('dashboard') ? 'color:var(--muted-1);' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
                </svg>
                <span class="font-medium">Painel</span>
            </a>

            <a href="{{ route('projects.index') }}"
                class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('projects.*') ? 'active' : '' }}"
                style="{{ !request()->routeIs('projects.*') ? 'color:var(--muted-1);' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
                <span class="font-medium">Projetos</span>
            </a>

            <a href="{{ route('ideas.index') }}"
                class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('ideas.*') ? 'active' : '' }}"
                style="{{ !request()->routeIs('ideas.*') ? 'color:var(--muted-1);' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347A5.002 5.002 0 0112 21a5.002 5.002 0 01-4.657-3.153l-.347-.347z"/>
                </svg>
                <span class="font-medium">Ideias</span>
            </a>
        </nav>

        {{-- Scan button --}}
        <div class="px-3 pb-5 pt-4" style="border-top:1px solid var(--border);">
            <form method="POST" action="{{ route('projects.scan') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium text-white transition-all hover:opacity-90 hover:shadow-lg"
                    style="background:linear-gradient(135deg,#059669,#0d9488); box-shadow:0 2px 8px rgba(5,150,105,.25);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Escanear Projetos
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main ─────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-h-full" style="margin-left:16rem;">

        {{-- Top bar --}}
        <header class="sticky top-0 z-10 flex items-center justify-between px-5 lg:px-8 h-14"
            style="background:rgba(13,17,23,.88); backdrop-filter:blur(14px); border-bottom:1px solid var(--border);">
            <h2 class="text-sm font-medium" style="color:var(--muted-1);">@yield('breadcrumb', 'Painel')</h2>
            <div class="flex items-center gap-2 text-xs" style="color:var(--muted-2);">
                <div class="w-1.5 h-1.5 rounded-full" style="background:#10b981; box-shadow:0 0 6px rgba(16,185,129,.6);"></div>
                online
            </div>
        </header>

        <main class="flex-1 px-5 lg:px-8 py-6">
            @if(session('success'))
            <div class="toast-enter mb-5 flex items-center gap-3 px-4 py-3 rounded-xl text-sm border"
                style="background:rgba(5,150,105,.12); border-color:rgba(5,150,105,.3); color:#34d399;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="toast-enter mb-5 flex items-center gap-3 px-4 py-3 rounded-xl text-sm border"
                style="background:rgba(239,68,68,.12); border-color:rgba(239,68,68,.3); color:#f87171;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
