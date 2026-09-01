@php
$sm = $project->status;
$git = $project->git_info;
$lastCommit = $git['commits'][0] ?? null;
$liveGit = $git ? $project->liveGitStatus() : null;
@endphp

<div class="card-hover rounded-2xl overflow-hidden group"
    style="background:var(--surface); border:1px solid var(--border);">

    {{-- Status accent bar --}}
    <div class="h-0.5 w-full" style="background:{{ $sm->bar }}; opacity:.7;"></div>

    <div class="p-5">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-2 mb-3">
            <a href="{{ route('projects.show', $project) }}" class="flex-1 min-w-0">
                <h4 class="font-semibold text-white leading-snug group-hover:text-indigo-300 transition-colors">{{ $project->name }}</h4>
            </a>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <span class="text-xs px-2 py-0.5 rounded-md font-medium whitespace-nowrap"
                    style="background:{{ $sm->bg }}; color:{{ $sm->color }};">{{ $sm->label }}</span>
                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                    onsubmit="return confirm('Remover este projeto do painel? Ele será ignorado em futuros scans.')">
                    @csrf @method('DELETE')
                    <button type="submit" aria-label="Remover projeto"
                        class="icon-action-danger w-6 h-6 rounded flex items-center justify-center">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <a href="{{ route('projects.show', $project) }}" class="block">
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
                        style="width:{{ $project->progress }}%; background:{{ $sm->bar }};"></div>
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
                    @if($liveGit && $liveGit['dirty'] !== null)
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                        style="background:{{ $liveGit['dirty'] ? '#fbbf24' : '#34d399' }};"
                        title="{{ $liveGit['dirty'] ? 'Working tree com alterações não commitadas' : 'Working tree limpo' }}"></span>
                    @endif
                    <span class="text-xs" style="color:var(--muted-2);">{{ $git['branch'] }}</span>
                    @if($liveGit && $liveGit['has_upstream'] && ($liveGit['ahead'] > 0 || $liveGit['behind'] > 0))
                    <span class="text-xs font-mono" style="color:var(--muted-2);" title="Commits à frente / atrás do remoto">
                        @if($liveGit['ahead'] > 0)↑{{ $liveGit['ahead'] }}@endif
                        @if($liveGit['behind'] > 0)↓{{ $liveGit['behind'] }}@endif
                    </span>
                    @endif
                </div>
                <p class="text-xs truncate mb-1" style="color:var(--muted-1);">{{ $lastCommit['message'] }}</p>
                @if($project->size_bytes !== null || !empty($git['contributors']))
                <p class="text-xs" style="color:var(--muted-2);">
                    @if($project->size_bytes !== null){{ $project->formatted_size }}@endif
                    @if($project->size_bytes !== null && !empty($git['contributors']))·@endif
                    @if(!empty($git['contributors'])){{ count($git['contributors']) }} {{ count($git['contributors']) === 1 ? 'contribuidor' : 'contribuidores' }}@endif
                </p>
                @endif
            </div>
            @endif
        </a>
    </div>
</div>
