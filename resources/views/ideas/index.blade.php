@extends('layouts.app')
@section('title', 'Ideias')
@section('breadcrumb', 'Ideias')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-white tracking-tight">Ideias</h1>
            <p class="text-sm mt-1" style="color:var(--muted-1);">Quadro Kanban para capturar e organizar suas ideias.</p>
        </div>
        <span class="text-xs px-3 py-1.5 rounded-lg border font-medium self-start sm:self-auto"
            style="background:var(--surface); border-color:var(--border); color:var(--muted-1);">
            {{ $ideas->flatten()->count() }} {{ $ideas->flatten()->count() === 1 ? 'ideia' : 'ideias' }}
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:min-h-[72vh]">
        @foreach($columns as $column)
        @php
            $columnIdeas = $ideas->get($column->code, collect());
        @endphp
        <div class="rounded-2xl flex flex-col border overflow-hidden"
            style="background:var(--surface); border-color:{{ $column->border }};">

            {{-- Column header --}}
            <div class="px-4 pt-4 pb-3" style="background:{{ $column->header_bg }};">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" style="background:{{ $column->color }};"></span>
                        <h3 class="text-xs font-bold uppercase tracking-widest" style="color:{{ $column->color }};">
                            {{ $column->label }}
                        </h3>
                    </div>
                    <span class="text-xs font-semibold w-5 h-5 flex items-center justify-center rounded-md"
                        style="background:rgba(255,255,255,.07); color:var(--muted-1);">
                        {{ $columnIdeas->count() }}
                    </span>
                </div>
            </div>

            {{-- Quick add (Raw) --}}
            @if($column->code === 'raw')
            <div class="px-3 py-2">
                <form method="POST" action="{{ route('ideas.store') }}" x-data="{open:false}">
                    @csrf
                    <input type="hidden" name="status" value="raw">
                    <div x-show="!open"
                        @click="open=true;$nextTick(()=>$el.nextElementSibling.querySelector('input').focus())"
                        class="text-xs text-center py-2 rounded-xl border border-dashed cursor-pointer transition-all"
                        style="color:var(--muted-2); border-color:var(--border-2);"
                        onmouseover="this.style.borderColor='#60a5fa'; this.style.color='#93c5fd';"
                        onmouseout="this.style.borderColor='var(--border-2)'; this.style.color='var(--muted-2)';">
                        + Nova ideia
                    </div>
                    <div x-show="open" x-cloak class="space-y-2">
                        <input type="text" name="title" placeholder="Nome da ideia..."
                            class="w-full text-sm rounded-xl px-3 py-2 border focus:outline-none"
                            style="background:var(--surface-2); border-color:var(--border-2); color:#e2e8f0;">
                        <div class="flex gap-1.5">
                            <button type="submit" class="flex-1 text-xs py-1.5 rounded-lg font-medium text-white"
                                style="background:#4f46e5;">Adicionar</button>
                            <button type="button" @click="open=false"
                                class="flex-1 text-xs py-1.5 rounded-lg font-medium"
                                style="background:var(--border); color:var(--muted-1);">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
            @endif

            {{-- Cards --}}
            <div class="flex-1 px-3 pb-3 space-y-2 overflow-y-auto">
                @foreach($columnIdeas as $idea)
                <div class="rounded-xl border group"
                    style="background:var(--surface-2); border-color:var(--border);">
                    <div class="p-3">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <a href="{{ route('ideas.show', $idea) }}"
                                class="text-sm font-medium leading-snug flex-1 hover:text-indigo-300 transition-colors"
                                style="color:#e2e8f0;">{{ $idea->title }}</a>
                            <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1"
                                style="background:{{ $idea->priority->dot }};" title="{{ $idea->priority->label }}"></span>
                        </div>
                        @if($idea->description)
                        <p class="text-xs leading-relaxed line-clamp-2" style="color:var(--muted-1);">{{ $idea->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-0.5 px-2 pb-2">
                        @if($column->code !== 'parked')
                        <form method="POST" action="{{ route('ideas.convert', $idea) }}">
                            @csrf
                            <button type="submit"
                                class="flex items-center gap-1 text-xs px-2 py-1 rounded-lg transition-colors"
                                style="color:#6ee7b7;"
                                onmouseover="this.style.background='rgba(16,185,129,.15)';"
                                onmouseout="this.style.background='transparent';">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                Converter
                            </button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('ideas.destroy', $idea) }}" class="ml-auto">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="text-xs px-2 py-1 rounded-lg transition-colors"
                                style="color:var(--muted-2);"
                                onmouseover="this.style.background='rgba(239,68,68,.15)'; this.style.color='#fca5a5';"
                                onmouseout="this.style.background='transparent'; this.style.color='var(--muted-2)';"
                                onclick="return confirm('Excluir esta ideia?')">Excluir</button>
                        </form>
                    </div>
                </div>
                @endforeach
                @if($columnIdeas->isEmpty())
                <div class="text-center py-8" style="color:var(--muted-3);">
                    <p class="text-xs">Nenhuma ideia aqui</p>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
