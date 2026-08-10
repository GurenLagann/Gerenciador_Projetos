@extends('layouts.app')
@section('title', 'Projetos')
@section('breadcrumb', 'Projetos')
@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-white tracking-tight">Projetos</h1>
            <p class="text-sm mt-1" style="color:var(--muted-1);">
                {{ $projects->total() }} {{ $projects->total() === 1 ? 'projeto' : 'projetos' }} no total
            </p>
        </div>
        <livewire:scanner-status />
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-2 flex-wrap">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
            <div class="flex items-center rounded-xl border overflow-hidden w-full sm:w-auto"
                style="background:var(--surface); border-color:var(--border);">
                <label class="px-3 text-xs font-semibold flex-shrink-0" style="color:var(--muted-2);">Status</label>
                <select name="status" onchange="this.form.submit()"
                    class="bg-transparent border-0 text-sm py-2.5 sm:py-2 pr-3 focus:ring-0 focus:outline-none cursor-pointer w-full"
                    style="color:var(--text);">
                    <option value="">Todos</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s->code }}" @selected(request('status') === $s->code)>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center rounded-xl border overflow-hidden w-full sm:w-auto"
                style="background:var(--surface); border-color:var(--border);">
                <label class="px-3 text-xs font-semibold flex-shrink-0" style="color:var(--muted-2);">Ordem</label>
                <select name="sort" onchange="this.form.submit()"
                    class="bg-transparent border-0 text-sm py-2.5 sm:py-2 pr-3 focus:ring-0 focus:outline-none cursor-pointer w-full"
                    style="color:var(--text);">
                    <option value="latest"   @selected(request('sort','latest')==='latest')>Mais Recente</option>
                    <option value="name"     @selected(request('sort')==='name')>Nome</option>
                    <option value="progress" @selected(request('sort')==='progress')>Progresso</option>
                    <option value="status"   @selected(request('sort')==='status')>Status</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Grid --}}
    @if($projects->isEmpty())
    <div class="rounded-2xl border border-dashed py-20 text-center" style="border-color:var(--border-2);">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
            style="background:rgba(99,102,241,.1); border:1px solid rgba(99,102,241,.2);">
            <svg class="w-7 h-7" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-white mb-1">Nenhum projeto encontrado</p>
        <p class="text-xs" style="color:var(--muted-1);">Use o botao acima para escanear e detectar seus projetos.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
        @foreach($projects as $project)
        @include('projects._card', ['project' => $project])
        @endforeach
    </div>
    <div class="mt-2">{{ $projects->links() }}</div>
    @endif

</div>
@endsection
