@extends('layouts.app')
@section('title', 'Painel')
@section('breadcrumb', 'Painel')
@section('content')
@php
$statusMeta = [
    'idea'        => ['label'=>'Ideia',       'color'=>'#94a3b8','bg'=>'rgba(148,163,184,.12)'],
    'planning'    => ['label'=>'Planejamento','color'=>'#60a5fa','bg'=>'rgba(96,165,250,.13)'],
    'in-progress' => ['label'=>'Em Andamento','color'=>'#a5b4fc','bg'=>'rgba(165,180,252,.13)'],
    'paused'      => ['label'=>'Pausado',     'color'=>'#fcd34d','bg'=>'rgba(252,211,77,.12)'],
    'done'        => ['label'=>'Concluído',   'color'=>'#6ee7b7','bg'=>'rgba(110,231,183,.12)'],
    'archived'    => ['label'=>'Arquivado',   'color'=>'#fca5a5','bg'=>'rgba(252,165,165,.12)'],
];
$statusBars = [
    'idea'=>'#64748b','planning'=>'#3b82f6','in-progress'=>'#6366f1',
    'paused'=>'#f59e0b','done'=>'#10b981','archived'=>'#ef4444',
];
@endphp

<div class="space-y-8 max-w-7xl">

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-semibold text-white tracking-tight">Bom trabalho</h1>
        <p class="text-sm mt-1" style="color:var(--muted-1);">Visao geral dos seus projetos e ideias.</p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $cards = [
            ['value'=>$stats['total_projects'], 'label'=>'Total de Projetos', 'accent'=>'#6366f1', 'glow'=>'rgba(99,102,241,.2)',
             'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>'],
            ['value'=>$stats['in_progress'],    'label'=>'Em Andamento',      'accent'=>'#818cf8', 'glow'=>'rgba(129,140,248,.2)',
             'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
            ['value'=>$stats['done'],           'label'=>'Concluidos',        'accent'=>'#34d399', 'glow'=>'rgba(52,211,153,.2)',
             'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            ['value'=>$stats['ideas'],          'label'=>'Ideias',            'accent'=>'#fbbf24', 'glow'=>'rgba(251,191,36,.2)',
             'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347A5.002 5.002 0 0112 21a5.002 5.002 0 01-4.657-3.153l-.347-.347z"/>'],
        ];
        @endphp
        @foreach($cards as $card)
        <div class="rounded-2xl p-5 border" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-start justify-between mb-5">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                    style="background:{{ $card['glow'] }}; border:1px solid {{ $card['accent'] }}22;">
                    <svg class="w-5 h-5" style="color:{{ $card['accent'] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $card['icon'] !!}
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white tabular-nums">{{ $card['value'] }}</div>
            <div class="text-sm mt-1" style="color:var(--muted-1);">{{ $card['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Status breakdown --}}
    @if($statusCounts->isNotEmpty())
    @php $total = $statusCounts->sum(); @endphp
    <div class="rounded-2xl p-5 border" style="background:var(--surface); border-color:var(--border);">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white">Distribuicao por Status</h3>
            <span class="text-xs" style="color:var(--muted-2);">{{ $total }} projetos</span>
        </div>
        <div class="flex h-2 rounded-full overflow-hidden gap-px mb-5" style="background:var(--border);">
            @foreach($statusCounts as $status => $count)
            @php $pct = $total > 0 ? round(($count/$total)*100) : 0; @endphp
            <div class="h-full transition-all rounded-sm" style="width:{{ $pct }}%; background:{{ $statusBars[$status] ?? '#64748b' }};"></div>
            @endforeach
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            @foreach($statusCounts as $status => $count)
            @php $m = $statusMeta[$status] ?? ['label'=>$status,'color'=>'#94a3b8','bg'=>'rgba(148,163,184,.1)']; @endphp
            <div class="flex items-center justify-between px-3 py-2 rounded-lg" style="background:{{ $m['bg'] }};">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $m['color'] }};"></span>
                    <span class="text-xs font-medium" style="color:{{ $m['color'] }};">{{ $m['label'] }}</span>
                </div>
                <span class="text-xs font-bold text-white">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent projects --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-white">Projetos Recentes</h3>
            <a href="{{ route('projects.index') }}" class="text-xs font-medium hover:opacity-80 transition-opacity" style="color:#818cf8;">
                Ver todos &rarr;
            </a>
        </div>
        @if($recentProjects->isEmpty())
        <div class="rounded-2xl border border-dashed py-14 text-center" style="border-color:var(--border-2);">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3"
                style="background:rgba(99,102,241,.1); border:1px solid rgba(99,102,241,.2);">
                <svg class="w-6 h-6" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-white mb-1">Nenhum projeto ainda</p>
            <p class="text-xs mb-4" style="color:var(--muted-1);">Escaneie a pasta de projetos para comecar.</p>
            <form method="POST" action="{{ route('projects.scan') }}" class="inline">
                @csrf
                <button type="submit" class="text-xs font-medium px-4 py-2 rounded-lg text-white"
                    style="background:rgba(99,102,241,.2); border:1px solid rgba(99,102,241,.3);">
                    Escanear agora
                </button>
            </form>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($recentProjects as $project)
            @include('projects._card', ['project' => $project])
            @endforeach
        </div>
        @endif
    </div>

    {{-- Recent ideas --}}
    @if($recentIdeas->isNotEmpty())
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-white">Ideias Recentes</h3>
            <a href="{{ route('ideas.index') }}" class="text-xs font-medium hover:opacity-80 transition-opacity" style="color:#818cf8;">
                Ver todas &rarr;
            </a>
        </div>
        <div class="rounded-2xl border overflow-hidden" style="background:var(--surface); border-color:var(--border);">
            @foreach($recentIdeas as $i => $idea)
            @php
                $pMeta = ['low'=>['label'=>'Baixa','dot'=>'#64748b'],'medium'=>['label'=>'Media','dot'=>'#f59e0b'],'high'=>['label'=>'Alta','dot'=>'#ef4444']];
                $pm = $pMeta[$idea->priority] ?? $pMeta['medium'];
                $sm = $statusMeta[$idea->status] ?? ['label'=>$idea->status,'color'=>'#94a3b8','bg'=>'rgba(148,163,184,.1)'];
            @endphp
            <a href="{{ route('ideas.show', $idea) }}"
                class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/[.025] transition-colors {{ $i > 0 ? 'border-t' : '' }}"
                style="{{ $i > 0 ? 'border-color:var(--border);' : '' }}">
                <div class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $pm['dot'] }};"></div>
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-white">{{ $idea->title }}</span>
                    @if($idea->description)
                    <span class="text-xs ml-2" style="color:var(--muted-1);">{{ Str::limit($idea->description, 60) }}</span>
                    @endif
                </div>
                <span class="text-xs px-2 py-1 rounded-md font-medium flex-shrink-0"
                    style="background:{{ $sm['bg'] }}; color:{{ $sm['color'] }};">{{ $sm['label'] }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
