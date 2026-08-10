@extends('layouts.app')
@section('title', $idea->title)
@section('breadcrumb')
<span style="color:var(--muted-2);">Ideias</span>
<span class="mx-1.5" style="color:var(--border-3);">/</span>
<span class="text-white">{{ Str::limit($idea->title, 40) }}</span>
@endsection
@section('content')
@php
$sm = $idea->status;
$pm = $idea->priority;
@endphp

<div class="space-y-6 max-w-6xl">

    <div class="rounded-2xl border p-6" style="background:var(--surface); border-color:var(--border);">
        <div class="flex flex-col sm:flex-row items-start gap-5">
            <div class="flex-1 min-w-0">
                <a href="{{ route('ideas.index') }}"
                    class="inline-flex items-center gap-1.5 text-xs mb-3 hover:opacity-80 transition-opacity"
                    style="color:var(--muted-1);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Ideias
                </a>
                <h1 class="text-2xl font-bold text-white tracking-tight mb-2">{{ $idea->title }}</h1>
                @if($idea->description)
                <p class="text-sm leading-relaxed mb-4" style="color:var(--muted-1);">{{ $idea->description }}</p>
                @endif
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium"
                        style="background:{{ $sm->bg }}; color:{{ $sm->color }};">{{ $sm->label }}</span>
                    <span class="text-xs flex items-center gap-1.5" style="color:var(--muted-1);">
                        <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $pm->dot }};"></span>
                        Prioridade {{ $pm->label }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:items-end gap-3 flex-shrink-0 w-full sm:w-auto">
                <form method="POST" action="{{ route('ideas.update', $idea) }}">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                        class="text-sm rounded-xl border px-3 py-2 focus:ring-0 focus:outline-none"
                        style="background:var(--surface-2); border-color:var(--border-2); color:var(--text);">
                        @foreach($statuses as $s)
                        <option value="{{ $s->code }}" @selected($idea->status_id === $s->id)
                            style="color:{{ $s->color }};">{{ $s->label }}</option>
                        @endforeach
                    </select>
                </form>

                @if($idea->status->code !== 'converted')
                <form method="POST" action="{{ route('ideas.convert', $idea) }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition-all hover:opacity-90"
                        style="background:linear-gradient(135deg,#059669,#0d9488); box-shadow:0 2px 8px rgba(5,150,105,.25);">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        Converter em Projeto
                    </button>
                </form>
                @else
                <a href="{{ route('projects.show', $idea->convertedProject) }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-opacity hover:opacity-80"
                    style="background:rgba(196,181,253,.1); border:1px solid rgba(196,181,253,.2); color:#c4b5fd;">
                    Ver Projeto
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                @endif
            </div>
        </div>
    </div>

    <livewire:annotation-editor :annotatable-type="'idea'" :annotatable-id="$idea->id" />

</div>
@endsection
