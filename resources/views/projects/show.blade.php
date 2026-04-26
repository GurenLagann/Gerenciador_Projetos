@extends('layouts.app')
@section('title', $project->name)
@section('breadcrumb')
<span style="color:var(--muted-2);">Projetos</span>
<span class="mx-1.5" style="color:var(--border-3);">/</span>
<span class="text-white">{{ $project->name }}</span>
@endsection
@section('content')
@php
$statusMeta = [
    'idea'        => ['label'=>'Ideia',       'color'=>'#94a3b8','bg'=>'rgba(148,163,184,.12)','bar'=>'#64748b'],
    'planning'    => ['label'=>'Planejamento','color'=>'#60a5fa','bg'=>'rgba(96,165,250,.13)', 'bar'=>'#3b82f6'],
    'in-progress' => ['label'=>'Em Andamento','color'=>'#a5b4fc','bg'=>'rgba(165,180,252,.13)','bar'=>'#6366f1'],
    'paused'      => ['label'=>'Pausado',     'color'=>'#fcd34d','bg'=>'rgba(252,211,77,.12)', 'bar'=>'#f59e0b'],
    'done'        => ['label'=>'Concluido',   'color'=>'#6ee7b7','bg'=>'rgba(110,231,183,.12)','bar'=>'#10b981'],
    'archived'    => ['label'=>'Arquivado',   'color'=>'#fca5a5','bg'=>'rgba(252,165,165,.12)','bar'=>'#ef4444'],
];
$sm = $statusMeta[$project->status] ?? $statusMeta['idea'];
$git = $project->git_info;
@endphp

<div class="space-y-6 max-w-6xl">

    {{-- Project header --}}
    <div class="rounded-2xl border overflow-hidden" style="background:var(--surface); border-color:var(--border);">
        <div class="h-0.5" style="background:{{ $sm['bar'] }};"></div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row items-start gap-5">
                <div class="flex-1 min-w-0">
                    <a href="{{ route('projects.index') }}"
                        class="inline-flex items-center gap-1.5 text-xs mb-3 hover:opacity-80 transition-opacity"
                        style="color:var(--muted-1);">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Projetos
                    </a>
                    <h1 class="text-2xl font-bold text-white tracking-tight mb-2">{{ $project->name }}</h1>
                    @if($project->description)
                    <p class="text-sm leading-relaxed mb-4" style="color:var(--muted-1);">{{ $project->description }}</p>
                    @endif
                    @if(!empty($project->tech_stack))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($project->tech_stack as $tech)
                        <span class="text-xs px-2 py-0.5 rounded font-medium"
                            style="background:rgba(99,102,241,.13); color:#a5b4fc;">{{ $tech }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="flex flex-col sm:items-end gap-3 flex-shrink-0 w-full sm:w-auto">
                    <form method="POST" action="{{ route('projects.status', $project) }}">
                        @csrf @method('PATCH')
                        <select name="status" onchange="this.form.submit()"
                            class="text-sm rounded-xl border px-3 py-2 font-medium focus:ring-0 focus:outline-none"
                            style="background:var(--surface-2); border-color:var(--border-2); color:{{ $sm['color'] }};">
                            @foreach(['idea','planning','in-progress','paused','done','archived'] as $s)
                            @php $m = $statusMeta[$s]; @endphp
                            <option value="{{ $s }}" @selected($project->status === $s)
                                style="color:{{ $m['color'] }};">{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if($git)
                    <div class="flex items-center gap-2 text-xs" style="color:var(--muted-2);">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span>{{ $git['branch'] }}</span>
                        <span style="color:var(--border-3);">·</span>
                        <span>{{ number_format($git['total_commits']) }} commits</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Progress --}}
            <div class="mt-6 pt-5 border-t" style="border-color:var(--border);"
                x-data="{ progress: {{ $project->progress }} }">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium" style="color:var(--muted-2);">Progresso</span>
                    <span class="text-xs font-semibold tabular-nums"
                        :style="progress >= 100 ? 'color:#6ee7b7;' : 'color:#94a3b8;'"
                        x-text="progress + '%'"></span>
                </div>
                <input type="range" min="0" max="100" x-model="progress" class="w-full"
                    @change="fetch('{{ route('projects.progress', $project) }}', {
                        method:'PATCH',
                        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                        body:JSON.stringify({progress:progress})
                    })">
                <div class="flex justify-between text-xs mt-1.5" style="color:var(--muted-3);">
                    <span>0%</span><span>50%</span><span>100%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Content grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Annotations (2/3) --}}
        <div class="col-span-2">
            <livewire:annotation-editor :annotatable-type="'project'" :annotatable-id="$project->id" />
        </div>

        {{-- Milestones (1/3) --}}
        <div class="rounded-2xl border p-5" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                    <svg class="w-4 h-4" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Marcos
                </h3>
                @if($project->milestones->count() > 0)
                <span class="text-xs font-medium tabular-nums px-2 py-0.5 rounded-md"
                    style="background:var(--surface-2); color:var(--muted-1);">
                    {{ $project->milestones->where('completed',true)->count() }}/{{ $project->milestones->count() }}
                </span>
                @endif
            </div>

            {{-- Add --}}
            <form method="POST" action="{{ route('milestones.store', $project) }}" class="mb-4"
                x-data="{open:false}">
                @csrf
                <div x-show="!open"
                    @click="open=true;$nextTick(()=>$el.nextElementSibling.querySelector('input').focus())"
                    class="text-xs text-center py-2.5 rounded-xl border border-dashed cursor-pointer transition-all"
                    style="color:var(--muted-2); border-color:var(--border-2);"
                    onmouseover="this.style.borderColor='#6366f1'; this.style.color='#a5b4fc';"
                    onmouseout="this.style.borderColor='var(--border-2)'; this.style.color='var(--muted-2)';">
                    + Novo marco
                </div>
                <div x-show="open" x-cloak class="space-y-2">
                    <input type="text" name="title" placeholder="Titulo do marco..."
                        class="w-full text-sm rounded-xl px-3 py-2 border focus:outline-none transition-colors focus:border-indigo-500"
                        style="background:var(--surface-2); border-color:var(--border-2); color:#e2e8f0;">
                    <div class="flex gap-1.5">
                        <button type="submit" class="flex-1 text-xs py-2 rounded-lg font-medium text-white"
                            style="background:#4f46e5;">Salvar</button>
                        <button type="button" @click="open=false"
                            class="flex-1 text-xs py-2 rounded-lg font-medium"
                            style="background:var(--border); color:var(--muted-1);">Cancelar</button>
                    </div>
                </div>
            </form>

            {{-- Milestone bar --}}
            @if($project->milestones->count() > 0)
            <div class="mb-3">
                <div class="w-full h-1 rounded-full" style="background:var(--border-2);">
                    <div class="h-1 rounded-full transition-all" style="width:{{ $project->milestone_progress }}%; background:#6366f1;"></div>
                </div>
            </div>
            @endif

            <div class="space-y-0.5">
                @forelse($project->milestones as $milestone)
                <div class="flex items-center gap-2.5 group px-2 py-2 rounded-xl hover:bg-white/[.03] transition-colors">
                    <form method="POST" action="{{ route('milestones.toggle', [$project, $milestone]) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                            style="{{ $milestone->completed
                                ? 'border-color:#6366f1; background:#6366f1;'
                                : 'border-color:var(--border-3);' }}">
                            @if($milestone->completed)
                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            @endif
                        </button>
                    </form>
                    <span class="flex-1 text-sm leading-snug"
                        style="{{ $milestone->completed ? 'color:var(--muted-2); text-decoration:line-through;' : 'color:#e2e8f0;' }}">
                        {{ $milestone->title }}
                    </span>
                    <form method="POST" action="{{ route('milestones.destroy', [$project, $milestone]) }}"
                        class="opacity-0 group-hover:opacity-100 transition-opacity">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="w-5 h-5 rounded flex items-center justify-center text-sm transition-colors hover:bg-red-900/30"
                            style="color:var(--muted-2);">&times;</button>
                    </form>
                </div>
                @empty
                <div class="text-center py-6" style="color:var(--muted-3);">
                    <p class="text-xs">Nenhum marco ainda.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Git history --}}
    @if($git && !empty($git['commits']))
    <div class="rounded-2xl border overflow-hidden" style="background:var(--surface); border-color:var(--border);">
        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--border);">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4" style="color:#6366f1;" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.93 8.5a4.002 4.002 0 0 1-7.86 0H.75a.75.75 0 0 1 0-1.5h3.32a4.002 4.002 0 0 1 7.86 0h3.32a.75.75 0 0 1 0 1.5zm-1.43-.75a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0z"/>
                </svg>
                Historico Git
            </h3>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5" style="color:var(--muted-1);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    {{ $git['branch'] }}
                </span>
                <span class="px-2 py-0.5 rounded-md font-medium" style="background:var(--surface-2); color:var(--muted-1);">
                    {{ number_format($git['total_commits']) }} commits
                </span>
            </div>
        </div>
        <div style="divide-color:var(--border);">
            @foreach($git['commits'] as $i => $commit)
            @php
                $date = new \DateTime($commit['date']);
                $diff = (new \DateTime())->diff($date);
                if ($diff->days === 0) $ago = 'hoje';
                elseif ($diff->days === 1) $ago = 'ontem';
                elseif ($diff->days < 7) $ago = $diff->days . 'd atras';
                elseif ($diff->days < 30) $ago = floor($diff->days/7) . 'sem atras';
                elseif ($diff->days < 365) $ago = floor($diff->days/30) . 'm atras';
                else $ago = floor($diff->days/365) . 'a atras';
            @endphp
            <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/[.02] transition-colors {{ $i > 0 ? 'border-t' : '' }}"
                style="{{ $i > 0 ? 'border-color:var(--border);' : '' }}">
                <code class="text-xs font-mono flex-shrink-0 px-2 py-0.5 rounded"
                    style="background:rgba(99,102,241,.12); color:#818cf8;">{{ $commit['hash'] }}</code>
                <p class="flex-1 text-sm truncate" style="color:#e2e8f0; opacity:.85;">{{ $commit['message'] }}</p>
                <span class="text-xs flex-shrink-0" style="color:var(--muted-2);">{{ $commit['author'] }}</span>
                <span class="text-xs flex-shrink-0 tabular-nums" style="color:var(--muted-3);">{{ $ago }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="flex items-center gap-5 text-xs pb-2" style="color:var(--muted-3);">
        @if($project->path)
        <span class="flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            {{ $project->path }}
        </span>
        @endif
        @if($project->last_scanned_at)
        <span class="flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Escaneado {{ $project->last_scanned_at->diffForHumans() }}
        </span>
        @endif
    </div>

</div>
@endsection
