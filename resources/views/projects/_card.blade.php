@php
$statusMeta = [
    'idea'        => ['label'=>'Ideia',       'color'=>'#94a3b8','bg'=>'rgba(148,163,184,.12)','bar'=>'#64748b'],
    'planning'    => ['label'=>'Planejamento','color'=>'#60a5fa','bg'=>'rgba(96,165,250,.13)', 'bar'=>'#3b82f6'],
    'in-progress' => ['label'=>'Em Andamento','color'=>'#a5b4fc','bg'=>'rgba(165,180,252,.13)','bar'=>'#6366f1'],
    'paused'      => ['label'=>'Pausado',     'color'=>'#fcd34d','bg'=>'rgba(252,211,77,.12)', 'bar'=>'#f59e0b'],
    'done'        => ['label'=>'Concluído',   'color'=>'#6ee7b7','bg'=>'rgba(110,231,183,.12)','bar'=>'#10b981'],
    'archived'    => ['label'=>'Arquivado',   'color'=>'#fca5a5','bg'=>'rgba(252,165,165,.12)','bar'=>'#ef4444'],
];
$sm = $statusMeta[$project->status] ?? $statusMeta['idea'];
$git = $project->git_info;
$lastCommit = $git['commits'][0] ?? null;
@endphp

<a href="{{ route('projects.show', $project) }}"
    class="card-hover block rounded-2xl overflow-hidden group"
    style="background:var(--surface); border:1px solid var(--border);">

    {{-- Status accent bar --}}
    <div class="h-0.5 w-full" style="background:{{ $sm['bar'] }}; opacity:.7;"></div>

    <div class="p-5">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-2 mb-3">
            <h4 class="font-semibold text-white leading-snug group-hover:text-indigo-300 transition-colors">{{ $project->name }}</h4>
            <span class="text-xs px-2 py-0.5 rounded-md font-medium flex-shrink-0 whitespace-nowrap"
                style="background:{{ $sm['bg'] }}; color:{{ $sm['color'] }};">{{ $sm['label'] }}</span>
        </div>

        @if($project->description)
        <p class="text-xs leading-relaxed mb-3 line-clamp-2" style="color:var(--muted-1);">{{ $project->description }}</p>
        @endif

        {{-- Tech stack --}}
        @if(!empty($project->tech_stack))
        <div class="flex flex-wrap gap-1.5 mb-4">
            @foreach(array_slice($project->tech_stack, 0, 4) as $tech)
            <span class="text-xs px-1.5 py-0.5 rounded font-medium"
                style="background:rgba(99,102,241,.13); color:#a5b4fc;">{{ $tech }}</span>
            @endforeach
            @if(count($project->tech_stack) > 4)
            <span class="text-xs px-1.5 py-0.5 rounded" style="color:var(--muted-2);">
                +{{ count($project->tech_stack) - 4 }}
            </span>
            @endif
        </div>
        @endif

        {{-- Progress --}}
        <div class="mb-4">
            <div class="flex justify-between items-center text-xs mb-1.5" style="color:var(--muted-2);">
                <span>Progresso</span>
                <span class="font-semibold tabular-nums"
                    style="color:{{ $project->progress >= 100 ? '#6ee7b7' : '#94a3b8' }};">{{ $project->progress }}%</span>
            </div>
            <div class="w-full h-1.5 rounded-full" style="background:var(--border-2);">
                <div class="h-1.5 rounded-full transition-all"
                    style="width:{{ $project->progress }}%; background:{{ $sm['bar'] }};"></div>
            </div>
        </div>

        {{-- Git info --}}
        @if($lastCommit)
        <div class="pt-3 border-t" style="border-color:var(--border);">
            <div class="flex items-center gap-1.5 mb-1">
                <svg class="w-3 h-3 flex-shrink-0" style="color:var(--muted-2);" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.93 8.5a4.002 4.002 0 0 1-7.86 0H.75a.75.75 0 0 1 0-1.5h3.32a4.002 4.002 0 0 1 7.86 0h3.32a.75.75 0 0 1 0 1.5zm-1.43-.75a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0z"/>
                </svg>
                <span class="text-xs font-mono" style="color:#818cf8;">{{ $lastCommit['hash'] }}</span>
                <span style="color:var(--border-3);">·</span>
                <span class="text-xs" style="color:var(--muted-2);">{{ $git['branch'] }}</span>
            </div>
            <p class="text-xs truncate" style="color:var(--muted-1);">{{ $lastCommit['message'] }}</p>
        </div>
        @endif
    </div>
</a>
