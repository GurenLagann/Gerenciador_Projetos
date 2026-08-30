@extends('layouts.app')
@section('title', 'Painel')
@section('breadcrumb', 'Painel')
@section('content')
@php
$total = $statusCounts->sum('count');

$statusChartData = [
    'labels' => $statusCounts->map(fn ($entry) => $entry->status->label)->values(),
    'values' => $statusCounts->map(fn ($entry) => $entry->count)->values(),
    'colors' => $statusCounts->map(fn ($entry) => $entry->status->color)->values(),
];

$ideaPipeline = $ideaStatusCounts->filter(fn ($entry) => $entry->status->is_board_column)->sortByDesc('count');
$ideaStatusChartData = [
    'labels' => $ideaPipeline->map(fn ($entry) => $entry->status->label)->values(),
    'values' => $ideaPipeline->map(fn ($entry) => $entry->count)->values(),
    'colors' => $ideaPipeline->map(fn ($entry) => $entry->status->color)->values(),
];

$ideaPriorityOrdered = collect(['low', 'medium', 'high'])->map(fn ($code) => $ideaPriorityCounts->get($code))->filter();
$ideaPriorityChartData = [
    'labels' => $ideaPriorityOrdered->map(fn ($entry) => $entry->priority->label)->values(),
    'values' => $ideaPriorityOrdered->map(fn ($entry) => $entry->count)->values(),
    'colors' => $ideaPriorityOrdered->map(fn ($entry) => $entry->priority->dot)->values(),
];

$paletteItems = collect([
    ['group' => 'Ações', 'icon' => 'action', 'label' => 'Escanear Projetos', 'hint' => 'ir para Projetos', 'url' => route('projects.index')],
    ['group' => 'Ações', 'icon' => 'action', 'label' => 'Nova Ideia', 'hint' => 'ir para Ideias', 'url' => route('ideas.index')],
])
    ->merge($paletteProjects->map(fn ($p) => ['group' => 'Projetos', 'icon' => 'project', 'label' => $p['label'], 'hint' => 'abrir', 'url' => $p['url']]))
    ->merge($paletteIdeas->map(fn ($i) => ['group' => 'Ideias', 'icon' => 'idea', 'label' => $i['label'], 'hint' => 'abrir', 'url' => $i['url']]))
    ->values();
@endphp

<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex items-baseline justify-between gap-4 flex-wrap">
        <h1 class="text-2xl font-semibold text-white tracking-tight">Bom trabalho</h1>
    </div>
    <p class="text-sm -mt-4" style="color:var(--muted-1);">
        Visão geral dos seus projetos e ideias. Pressione
        <span class="font-medium" style="font-family:var(--font-mono); color:var(--muted-2);">⌘K</span>
        para buscar rápido.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 auto-rows-[minmax(88px,auto)]">

        {{-- Hero: Em Andamento --}}
        <div class="sm:col-span-2 lg:row-span-2 rounded-2xl p-6 border flex flex-col" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest" style="color:var(--muted-2);">
                <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Em Andamento
            </div>
            <div class="text-5xl font-medium mt-3" style="font-family:var(--font-mono); color:white; font-variant-numeric:tabular-nums;">{{ $stats['in_progress'] }}</div>
            <div class="text-sm mt-1 mb-5" style="color:var(--muted-1);">projetos ativos agora</div>
            <div class="space-y-2.5 mt-auto max-w-sm">
                @forelse($activeProjects as $p)
                <a href="{{ route('projects.show', $p) }}" class="flex items-center gap-2.5 text-sm hover:opacity-80 transition-opacity">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $p->status->color }};"></span>
                    <span class="flex-1 min-w-0 truncate" style="color:var(--text);">{{ $p->name }}</span>
                    <span class="tabular-nums flex-shrink-0" style="font-family:var(--font-mono); font-size:.75rem; color:var(--muted-2);">{{ $p->progress }}%</span>
                </a>
                @empty
                <p class="text-sm" style="color:var(--muted-3);">Nenhum projeto em andamento.</p>
                @endforelse
            </div>
        </div>

        {{-- Distribuição por status (ring) --}}
        <div class="sm:col-span-2 lg:row-span-2 rounded-2xl p-6 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-4" style="color:var(--muted-2);">
                <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Distribuição por Status
            </div>
            @if($statusCounts->isEmpty())
            <p class="text-sm" style="color:var(--muted-3);">Sem projetos ainda.</p>
            @else
            <div class="flex items-center gap-6 max-w-xs">
                <div class="relative w-28 h-28 flex-shrink-0">
                    <canvas id="statusChart" width="112" height="112"
                        data-labels="{{ json_encode($statusChartData['labels']) }}"
                        data-values="{{ json_encode($statusChartData['values']) }}"
                        data-colors="{{ json_encode($statusChartData['colors']) }}"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-xl font-semibold" style="font-family:var(--font-mono); color:white;">{{ $total }}</span>
                        <span class="text-[10px]" style="color:var(--muted-2);">total</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0 space-y-1.5">
                    @foreach($statusCounts as $entry)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $entry->status->color }};"></span>
                        <span class="flex-1" style="color:var(--text);">{{ $entry->status->label }}</span>
                        <span class="tabular-nums" style="font-family:var(--font-mono); color:var(--muted-2);">{{ $entry->count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Small stat tiles --}}
        <div class="rounded-2xl p-5 border flex flex-col justify-center" style="background:var(--surface); border-color:var(--border);">
            <div class="text-2xl font-medium" style="font-family:var(--font-mono); color:white;">{{ $stats['total_projects'] }}</div>
            <div class="text-sm mt-0.5" style="color:var(--muted-1);">Total de Projetos</div>
        </div>
        <div class="rounded-2xl p-5 border flex flex-col justify-center" style="background:var(--surface); border-color:var(--border);">
            <div class="text-2xl font-medium" style="font-family:var(--font-mono); color:white;">{{ $stats['done'] }}</div>
            <div class="text-sm mt-0.5" style="color:var(--muted-1);">Concluídos</div>
        </div>

        {{-- Command palette launcher tile --}}
        <button type="button" id="paletteTile"
            class="sm:col-span-2 rounded-2xl p-5 border border-dashed flex items-center justify-center gap-3 transition-colors"
            style="border-color:var(--border-2); color:var(--muted-1);"
            onmouseover="this.style.borderColor='#6366f1'; this.style.background='rgba(99,102,241,.05)';"
            onmouseout="this.style.borderColor='var(--border-2)'; this.style.background='transparent';">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(99,102,241,.15); color:#a5b4fc;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                </svg>
            </div>
            <span class="text-sm font-medium" style="color:var(--text);">Busca rápida</span>
            <span class="flex gap-1 ml-1">
                <kbd class="text-xs px-1.5 py-0.5 rounded" style="font-family:var(--font-mono); background:var(--surface-3); border:1px solid var(--border-2); color:var(--muted-1);">⌘</kbd>
                <kbd class="text-xs px-1.5 py-0.5 rounded" style="font-family:var(--font-mono); background:var(--surface-3); border:1px solid var(--border-2); color:var(--muted-1);">K</kbd>
            </span>
        </button>

        {{-- Pipeline de Ideias --}}
        <div class="col-span-full flex items-center gap-2 text-xs font-bold uppercase tracking-widest mt-2" style="color:var(--muted-2);">
            Pipeline de Ideias
        </div>

        <div class="sm:col-span-2 rounded-2xl p-6 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-4" style="color:var(--muted-2);">
                <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347A5.002 5.002 0 0112 21a5.002 5.002 0 01-4.657-3.153l-.347-.347z"/>
                </svg>
                Por Status
            </div>
            @if($ideaStatusChartData['values']->isEmpty())
            <p class="text-sm" style="color:var(--muted-3);">Nenhuma ideia ainda.</p>
            @else
            <canvas id="ideaStatusChart" height="140"
                data-labels="{{ json_encode($ideaStatusChartData['labels']) }}"
                data-values="{{ json_encode($ideaStatusChartData['values']) }}"
                data-colors="{{ json_encode($ideaStatusChartData['colors']) }}"></canvas>
            @endif
        </div>

        <div class="sm:col-span-2 rounded-2xl p-6 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-4" style="color:var(--muted-2);">
                <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 21V9m0 12h4m-4-8l6-6 4 3 6-6"/>
                </svg>
                Por Prioridade
            </div>
            @if($ideaPriorityChartData['values']->isEmpty())
            <p class="text-sm" style="color:var(--muted-3);">Nenhuma ideia ainda.</p>
            @else
            <canvas id="ideaPriorityChart" height="100"
                data-labels="{{ json_encode($ideaPriorityChartData['labels']) }}"
                data-values="{{ json_encode($ideaPriorityChartData['values']) }}"
                data-colors="{{ json_encode($ideaPriorityChartData['colors']) }}"></canvas>
            @endif
        </div>

        {{-- Saúde do Projeto --}}
        <div class="col-span-full flex items-center gap-2 text-xs font-bold uppercase tracking-widest mt-2" style="color:var(--muted-2);">
            Saúde do Projeto
        </div>

        <div class="sm:col-span-2 rounded-2xl p-6 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-4" style="color:var(--muted-2);">
                <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                Débito Técnico
            </div>
            @if($openDebtCount === 0)
            <p class="text-sm" style="color:var(--muted-3);">Nenhum débito técnico registrado.</p>
            @else
            <div class="text-3xl font-medium" style="font-family:var(--font-mono); color:white; font-variant-numeric:tabular-nums;">{{ $openDebtCount }}</div>
            <div class="text-sm mt-0.5 mb-4" style="color:var(--muted-1);">débitos técnicos em aberto</div>
            <div class="space-y-2">
                @foreach($topDebtProjects as $p)
                <a href="{{ route('projects.show', $p) }}" class="flex items-center gap-2.5 text-sm hover:opacity-80 transition-opacity">
                    <span class="flex-1 min-w-0 truncate" style="color:var(--text);">{{ $p->name }}</span>
                    <span class="tabular-nums flex-shrink-0 text-xs px-1.5 py-0.5 rounded font-medium" style="font-family:var(--font-mono); background:var(--surface-3); color:var(--muted-2);">{{ $p->open_debt_count }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        <div class="sm:col-span-2 rounded-2xl p-6 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-4" style="color:var(--muted-2);">
                <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Marcos Próximos
            </div>
            @if($upcomingMilestones->isEmpty())
            <p class="text-sm" style="color:var(--muted-3);">Nenhum marco pendente.</p>
            @else
            <div class="space-y-2">
                @foreach($upcomingMilestones as $milestone)
                @php $overdue = $milestone->due_date->isPast(); @endphp
                <a href="{{ route('projects.show', $milestone->project) }}" class="flex items-center gap-2.5 text-sm hover:opacity-80 transition-opacity">
                    <div class="min-w-0 flex-1">
                        <div class="truncate" style="color:var(--text);">{{ $milestone->title }}</div>
                        <div class="text-[11px] truncate" style="color:var(--muted-3);">{{ $milestone->project->name }}</div>
                    </div>
                    @if($overdue)
                    <span class="flex-shrink-0 text-[11px] px-1.5 py-0.5 rounded font-medium" style="background:rgba(239,68,68,.15); color:#fca5a5;">atrasado</span>
                    @else
                    <span class="tabular-nums flex-shrink-0 text-[11px]" style="font-family:var(--font-mono); color:var(--muted-2);">{{ $milestone->due_date->format('d/m') }}</span>
                    @endif
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Projetos Recentes --}}
        <div class="sm:col-span-2 lg:col-span-3 lg:row-span-2 rounded-2xl p-6 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest" style="color:var(--muted-2);">
                    <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>
                    Projetos Recentes
                </div>
                <a href="{{ route('projects.index') }}" class="text-xs font-medium hover:opacity-80 transition-opacity" style="color:#818cf8;">Ver todos &rarr;</a>
            </div>
            @if($recentProjects->isEmpty())
            <div class="rounded-2xl border border-dashed py-10 text-center" style="border-color:var(--border-2);">
                <p class="text-sm font-medium text-white mb-1">Nenhum projeto ainda</p>
                <p class="text-xs mb-4" style="color:var(--muted-1);">Escaneie a pasta de projetos para começar.</p>
                <div class="inline-block">
                    <livewire:scanner-status />
                </div>
            </div>
            @else
            <div class="flex gap-3 overflow-x-auto pb-1">
                @foreach($recentProjects as $project)
                <a href="{{ route('projects.show', $project) }}"
                    class="flex-shrink-0 w-52 rounded-xl p-3.5 border hover:border-indigo-500/40 transition-colors"
                    style="background:var(--surface-2); border-color:var(--border);">
                    <div class="text-sm font-semibold truncate mb-2" style="color:white;">{{ $project->name }}</div>
                    <span class="text-[11px] px-1.5 py-0.5 rounded font-medium"
                        style="background:{{ $project->status->bg }}; color:{{ $project->status->color }};">{{ $project->status->label }}</span>
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-[11px]" style="color:var(--muted-2);">progresso</span>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center"
                            style="background: conic-gradient({{ $project->status->color }} {{ $project->progress * 3.6 }}deg, var(--border-2) 0deg);">
                            <div class="w-[18px] h-[18px] rounded-full flex items-center justify-center text-[9px]" style="font-family:var(--font-mono); background:var(--surface-2); color:var(--muted-1);">{{ $project->progress }}</div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Ideias Recentes --}}
        <div class="sm:col-span-2 lg:col-span-1 lg:row-span-2 rounded-2xl p-6 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest mb-4" style="color:var(--muted-2);">
                <svg class="w-3.5 h-3.5" style="color:#818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347A5.002 5.002 0 0112 21a5.002 5.002 0 01-4.657-3.153l-.347-.347z"/>
                </svg>
                Ideias
            </div>
            @if($recentIdeas->isEmpty())
            <p class="text-sm" style="color:var(--muted-3);">Nenhuma ideia ainda.</p>
            @else
            <div class="space-y-1">
                @foreach($recentIdeas as $idea)
                <a href="{{ route('ideas.show', $idea) }}" class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-white/[.03] transition-colors">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 mt-1.5" style="background:{{ $idea->priority->dot }};"></span>
                    <div class="min-w-0">
                        <div class="text-sm leading-snug truncate" style="color:var(--text);">{{ $idea->title }}</div>
                        <span class="text-[11px] px-1.5 py-0.5 rounded font-medium inline-block mt-1"
                            style="background:{{ $idea->status->bg }}; color:{{ $idea->status->color }};">{{ $idea->status->label }}</span>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Command palette (⌘K) --}}
<div id="cmdkBackdrop" class="fixed inset-0 z-50 flex items-start justify-center opacity-0 pointer-events-none transition-opacity"
    role="dialog" aria-modal="true" aria-label="Busca rápida"
    style="background:rgba(6,9,16,.6); backdrop-filter:blur(3px); padding-top:12vh;">
    <div id="cmdkPanel" class="w-full max-w-lg mx-4 rounded-2xl border overflow-hidden transition-transform"
        style="background:var(--surface); border-color:var(--border-2); box-shadow:0 24px 60px rgba(0,0,0,.5); transform:translateY(-8px) scale(.98);">
        <div class="flex items-center gap-2.5 px-4 py-3.5 border-b" style="border-color:var(--border);">
            <svg class="w-4 h-4 flex-shrink-0" style="color:var(--muted-2);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            <input id="cmdkInput" type="text" autocomplete="off" spellcheck="false"
                placeholder="Buscar projetos, ideias ou uma ação…" aria-label="Buscar projetos, ideias ou uma ação"
                role="combobox" aria-expanded="true" aria-controls="cmdkResults"
                class="flex-1 bg-transparent border-0 outline-none text-sm" style="color:white;">
            <kbd class="text-xs px-1.5 py-0.5 rounded flex-shrink-0" style="font-family:var(--font-mono); background:var(--surface-3); border:1px solid var(--border-2); color:var(--muted-2);">Esc</kbd>
        </div>
        <div id="cmdkResults" role="listbox" aria-label="Resultados da busca" class="max-h-80 overflow-y-auto p-2"></div>
    </div>
</div>

@push('scripts')
@vite('resources/js/dashboard.js')
<script>
(function () {
    var ICONS = {
        action: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        project: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>',
        idea: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347A5.002 5.002 0 0112 21a5.002 5.002 0 01-4.657-3.153l-.347-.347z"/>'
    };

    var ITEMS = @json($paletteItems);

    var backdrop = document.getElementById('cmdkBackdrop');
    var panel = document.getElementById('cmdkPanel');
    var input = document.getElementById('cmdkInput');
    var results = document.getElementById('cmdkResults');
    var activeIndex = 0;
    var visible = [];

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function render(query) {
        var q = (query || '').toLowerCase().trim();
        visible = ITEMS.filter(function (it) { return it.label.toLowerCase().indexOf(q) !== -1; });
        activeIndex = 0;

        if (!visible.length) {
            results.innerHTML = '<div class="text-center py-6 text-sm" style="color:var(--muted-2);">Nada encontrado.</div>';
            return;
        }

        var html = '';
        var lastGroup = null;
        visible.forEach(function (it, i) {
            if (it.group !== lastGroup) {
                html += '<div class="text-[10px] font-bold uppercase tracking-wider px-2.5 pt-2 pb-1" style="color:var(--muted-3);">' + it.group + '</div>';
                lastGroup = it.group;
            }
            html += '<div class="cmdk-item flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer text-sm' + (i === activeIndex ? ' active' : '') + '" role="option" aria-selected="' + (i === activeIndex ? 'true' : 'false') + '" data-i="' + i + '" style="' + (i === activeIndex ? 'background:rgba(99,102,241,.14);' : '') + ' color:var(--text);">' +
                '<svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--muted-2);" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + ICONS[it.icon] + '</svg>' +
                '<span class="flex-1 truncate">' + escapeHtml(it.label) + '</span>' +
                '<span class="text-xs flex-shrink-0" style="color:var(--muted-3);">' + escapeHtml(it.hint) + '</span>' +
            '</div>';
        });
        results.innerHTML = html;
    }

    function highlight() {
        Array.prototype.forEach.call(results.querySelectorAll('.cmdk-item'), function (el) {
            var isActive = Number(el.dataset.i) === activeIndex;
            el.style.background = isActive ? 'rgba(99,102,241,.14)' : '';
            el.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        var activeEl = results.querySelector('.cmdk-item[data-i="' + activeIndex + '"]');
        if (activeEl) activeEl.scrollIntoView({ block: 'nearest' });
    }

    function open() {
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        panel.style.transform = 'translateY(0) scale(1)';
        input.value = '';
        render('');
        setTimeout(function () { input.focus(); }, 20);
    }

    function close() {
        backdrop.classList.add('opacity-0', 'pointer-events-none');
        panel.style.transform = 'translateY(-8px) scale(.98)';
    }

    function select() {
        var item = visible[activeIndex];
        if (!item) return;
        window.location.href = item.url;
    }

    document.getElementById('paletteTile').addEventListener('click', open);
    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
    input.addEventListener('input', function () { render(input.value); });
    results.addEventListener('click', function (e) {
        var item = e.target.closest('.cmdk-item');
        if (!item) return;
        activeIndex = Number(item.dataset.i);
        select();
    });

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            backdrop.classList.contains('opacity-0') ? open() : close();
            return;
        }
        if (backdrop.classList.contains('opacity-0')) return;
        if (e.key === 'Escape') { close(); return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, visible.length - 1); highlight(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, 0); highlight(); }
        if (e.key === 'Enter') { e.preventDefault(); select(); }
    });
})();
</script>
@endpush
@endsection
