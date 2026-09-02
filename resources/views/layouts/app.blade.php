<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Project Manager') }} — @yield('title', 'Painel')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
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
            --muted-3:   #718aa2;
            --muted-2:   #748ba4;
            --muted-1:   #7890aa;
            --text:      #c9d8ee;
            --surface-3: #212c40;
            --font-mono: 'JetBrains Mono', ui-monospace, 'SF Mono', 'Cascadia Code', Consolas, monospace;
        }
        html, body { overflow-x: hidden; }
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

        /* Icon-only action buttons: shared hover + keyboard-focus states */
        .icon-action { color: var(--muted-2); transition: background-color .15s ease, color .15s ease; }
        .icon-action:hover, .icon-action:focus-visible { background: rgba(255,255,255,.06); color: #e2e8f0; }
        .icon-action-danger { color: var(--muted-2); transition: background-color .15s ease, color .15s ease; }
        .icon-action-danger:hover, .icon-action-danger:focus-visible { background: rgba(239,68,68,.15); color: #fca5a5; }
        .icon-action-success { color: #6ee7b7; transition: background-color .15s ease; }
        .icon-action-success:hover, .icon-action-success:focus-visible { background: rgba(16,185,129,.15); }

        /* Dashed "add new" triggers */
        .outline-trigger { color: var(--muted-2); border-color: var(--border-2); transition: border-color .15s ease, color .15s ease; }
        .outline-trigger:hover, .outline-trigger:focus-visible { border-color: #6366f1; color: #a5b4fc; }

        /* Outlined danger button (e.g. "Remover projeto") */
        .btn-outline-danger { border-color: var(--border-2); color: var(--muted-1); transition: background-color .15s ease, color .15s ease, border-color .15s ease; }
        .btn-outline-danger:hover, .btn-outline-danger:focus-visible { background: rgba(239,68,68,.12); color: #fca5a5; border-color: rgba(239,68,68,.3); }

        .icon-action:focus-visible, .icon-action-danger:focus-visible, .icon-action-success:focus-visible,
        .outline-trigger:focus-visible, .btn-outline-danger:focus-visible {
            outline: 2px solid #6366f1; outline-offset: 2px;
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

        /* Corpo de anotação recolhido: ~10 linhas, com fade para a cor do card */
        .note-clamp { max-height: 16rem; overflow: hidden; position: relative; }
        .note-clamp::after {
            content:''; position:absolute; left:0; right:0; bottom:0; height:3rem;
            background:linear-gradient(to bottom, rgba(22,30,45,0), var(--surface));
            pointer-events:none;
        }

        /* Inline meta row: middot separators without empty spans */
        .meta-inline { display:flex; flex-wrap:wrap; align-items:center; column-gap:.6rem; row-gap:.35rem; }
        .meta-inline > * + *::before { content:'·'; color:var(--border-3); margin-right:.6rem; }

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
<div class="flex h-full" x-data="{ mobileNavOpen: false }" @keydown.escape.window="mobileNavOpen = false">

    {{-- ── Mobile backdrop ───────────────────────────────────── --}}
    <div x-show="mobileNavOpen" x-cloak @click="mobileNavOpen = false"
        class="fixed inset-0 z-30 lg:hidden" style="background:rgba(6,9,16,.65);"
        x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    {{-- ── Sidebar ────────────────────────────────────────── --}}
    <aside
        class="w-64 flex flex-col fixed inset-y-0 z-40 -translate-x-full lg:translate-x-0 transition-transform duration-200"
        :class="{ 'translate-x-0': mobileNavOpen }"
        style="background:#141c2b; border-right:1px solid var(--border);">

        {{-- Logo --}}
        <div class="px-5 py-5 flex items-center justify-between" style="border-bottom:1px solid var(--border);">
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
            <button type="button" @click="mobileNavOpen = false" aria-label="Fechar menu"
                class="lg:hidden w-11 h-11 flex items-center justify-center rounded-lg flex-shrink-0" style="color:var(--muted-1);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            <p class="text-xs font-semibold px-3 mb-2" style="color:var(--muted-3); letter-spacing:.08em;">MENU</p>

            <a href="{{ route('dashboard') }}"
                class="sidebar-link flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                style="{{ !request()->routeIs('dashboard') ? 'color:var(--muted-1);' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
                </svg>
                <span class="font-medium">Painel</span>
            </a>

            <a href="{{ route('projects.index') }}"
                class="sidebar-link flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg text-sm {{ request()->routeIs('projects.*') ? 'active' : '' }}"
                style="{{ !request()->routeIs('projects.*') ? 'color:var(--muted-1);' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
                <span class="font-medium">Projetos</span>
            </a>

            <a href="{{ route('ideas.index') }}"
                class="sidebar-link flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg text-sm {{ request()->routeIs('ideas.*') ? 'active' : '' }}"
                style="{{ !request()->routeIs('ideas.*') ? 'color:var(--muted-1);' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347A5.002 5.002 0 0112 21a5.002 5.002 0 01-4.657-3.153l-.347-.347z"/>
                </svg>
                <span class="font-medium">Ideias</span>
            </a>
        </nav>

        {{-- Scan button --}}
        <div class="px-3 pb-5 pt-4" style="border-top:1px solid var(--border);">
            <livewire:scanner-status :full-width="true" />
        </div>
    </aside>

    {{-- ── Main ─────────────────────────────────────────────── --}}
    <div class="flex-1 min-w-0 flex flex-col min-h-full lg:ml-64">

        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 px-4 lg:px-8 h-14"
            style="background:rgba(13,17,23,.88); backdrop-filter:blur(14px); border-bottom:1px solid var(--border);">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="mobileNavOpen = true" aria-label="Abrir menu"
                    class="lg:hidden w-11 h-11 -ml-1.5 flex items-center justify-center rounded-lg flex-shrink-0" style="color:var(--muted-1);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h2 class="text-sm font-medium truncate" style="color:var(--muted-1);">@yield('breadcrumb', 'Painel')</h2>
            </div>
            <div class="hidden sm:flex items-center gap-2 text-xs flex-shrink-0" style="color:var(--muted-2);">
                <div class="w-1.5 h-1.5 rounded-full" style="background:#10b981; box-shadow:0 0 6px rgba(16,185,129,.6);"></div>
                online
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-5 lg:px-8 py-6">
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
<script>
    window.addEventListener('scan-complete', () => {
        setTimeout(() => window.location.reload(), 700);
    });
</script>
@stack('scripts')
</body>
</html>
