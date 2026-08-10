<div>
    <button wire:click="scan" wire:loading.attr="disabled"
        class="flex items-center gap-2 {{ $fullWidth ? 'w-full justify-center px-3 py-2.5' : 'px-4 py-2' }} rounded-xl text-sm font-medium text-white transition-all hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed"
        style="background:linear-gradient(135deg,#059669,#0d9488); box-shadow:0 2px 8px rgba(5,150,105,.25);">
        <svg wire:loading.remove class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <svg wire:loading class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span wire:loading.remove>Escanear Projetos</span>
        <span wire:loading>Escaneando…</span>
    </button>
    @if($message)
    <p class="mt-2 text-xs flex items-center gap-1.5" style="color:#34d399;">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ $message }}
    </p>
    @endif
</div>
