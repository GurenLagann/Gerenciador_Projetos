<div class="space-y-4">

    
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            <svg class="w-4 h-4" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Anotacoes
        </h3>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$showForm): ?>
        <button wire:click="$set('showForm', true)"
            class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border font-medium transition-all"
            style="background:var(--surface); border-color:var(--border-2); color:var(--muted-1);"
            onmouseover="this.style.borderColor='#6366f1'; this.style.color='#a5b4fc';"
            onmouseout="this.style.borderColor='var(--border-2)'; this.style.color='var(--muted-1)';">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Adicionar Nota
        </button>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showForm): ?>
    <div class="rounded-2xl border overflow-hidden"
        style="background:var(--surface); border-color:var(--border-2);"
        x-data="{ tab: 'write' }">

        
        <div class="flex items-center border-b px-4 pt-3" style="border-color:var(--border);">
            <button type="button" @click="tab='write'"
                class="text-xs font-medium px-3 py-2 border-b-2 transition-all"
                :class="tab==='write'
                    ? 'border-indigo-500 text-indigo-300'
                    : 'border-transparent text-slate-500 hover:text-slate-300'">
                Escrever
            </button>
            <button type="button" @click="tab='preview'"
                class="text-xs font-medium px-3 py-2 border-b-2 transition-all"
                :class="tab==='preview'
                    ? 'border-indigo-500 text-indigo-300'
                    : 'border-transparent text-slate-500 hover:text-slate-300'">
                Visualizar
            </button>
            <div class="ml-auto pb-2">
                <span class="text-xs px-2 py-0.5 rounded font-medium"
                    style="background:rgba(99,102,241,.12); color:#818cf8;">Markdown</span>
            </div>
        </div>

        <div class="p-4 space-y-3">
            <input type="text" wire:model="title" placeholder="Titulo (opcional)"
                class="w-full text-sm rounded-xl px-3 py-2 border focus:outline-none transition-colors focus:border-indigo-500"
                style="background:var(--surface-2); border-color:var(--border-2); color:#e2e8f0;">

            <div x-show="tab==='write'">
                <textarea wire:model="content" rows="6"
                    placeholder="Escreva sua nota em Markdown...

**negrito**, *italico*, `codigo`, ## titulo"
                    class="w-full text-sm rounded-xl px-3 py-2.5 border focus:outline-none transition-colors focus:border-indigo-500 font-mono resize-none leading-relaxed"
                    style="background:var(--surface-2); border-color:var(--border-2); color:#c9d8ee;"></textarea>
            </div>

            <div x-show="tab==='preview'" x-cloak
                class="min-h-36 px-3 py-2.5 rounded-xl border text-sm prose-pm"
                style="background:var(--surface-2); border-color:var(--border-2);">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(trim($content)): ?>
                    <?php echo $this->renderMarkdown($content); ?>

                <?php else: ?>
                    <p style="color:var(--muted-3); font-style:italic;">Nada para visualizar ainda...</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <button wire:click="save"
                    class="px-4 py-2 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90"
                    style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                    <?php echo e($editingId ? 'Atualizar' : 'Salvar'); ?>

                </button>
                <button wire:click="$set('showForm', false); $set('editingId', null); $set('title', ''); $set('content', '')"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                    style="background:var(--border); color:var(--muted-1);">
                    Cancelar
                </button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingId): ?>
                <span class="text-xs ml-auto" style="color:var(--muted-3);">Editando anotacao</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="space-y-3">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $annotations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $annotation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="rounded-2xl border group transition-all"
            style="background:var(--surface); border-color:<?php echo e($annotation->pinned ? 'rgba(99,102,241,.4)' : 'var(--border)'); ?>;">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($annotation->pinned): ?>
            <div class="h-0.5 rounded-t-2xl" style="background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="p-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($annotation->pinned): ?>
                        <svg class="w-3 h-3 flex-shrink-0" style="color:#818cf8;" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.342 1.174-.646 1.479-.126.125-.25.224-.354.298v4.431l.078.048c.203.127.476.314.751.555C12.36 7.775 13 8.527 13 9.5a.5.5 0 0 1-.5.5h-4v4.5c0 .276-.224 1.5-.5 1.5s-.5-1.224-.5-1.5V10h-4a.5.5 0 0 1-.5-.5c0-.973.64-1.725 1.17-2.189A5.921 5.921 0 0 1 5 6.708V2.277a2.77 2.77 0 0 1-.354-.298C4.342 1.674 4 1.179 4 .5a.5.5 0 0 1 .146-.354z"/>
                        </svg>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($annotation->title): ?>
                        <h4 class="text-sm font-semibold text-white truncate"><?php echo e($annotation->title); ?></h4>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="flex items-center gap-0.5 flex-shrink-0">
                        <button wire:click="pin(<?php echo e($annotation->id); ?>)"
                            class="p-1.5 rounded-lg transition-all text-xs"
                            style="<?php echo e($annotation->pinned ? 'color:#818cf8; background:rgba(99,102,241,.15);' : 'color:var(--muted-2);'); ?>"
                            title="<?php echo e($annotation->pinned ? 'Desafixar' : 'Fixar'); ?>">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.342 1.174-.646 1.479-.126.125-.25.224-.354.298v4.431l.078.048c.203.127.476.314.751.555C12.36 7.775 13 8.527 13 9.5a.5.5 0 0 1-.5.5h-4v4.5c0 .276-.224 1.5-.5 1.5s-.5-1.224-.5-1.5V10h-4a.5.5 0 0 1-.5-.5c0-.973.64-1.725 1.17-2.189A5.921 5.921 0 0 1 5 6.708V2.277a2.77 2.77 0 0 1-.354-.298C4.342 1.674 4 1.179 4 .5a.5.5 0 0 1 .146-.354z"/>
                            </svg>
                        </button>
                        <button wire:click="edit(<?php echo e($annotation->id); ?>)"
                            class="p-1.5 rounded-lg transition-all"
                            style="color:var(--muted-2);"
                            onmouseover="this.style.background='rgba(255,255,255,.06)'; this.style.color='#e2e8f0';"
                            onmouseout="this.style.background='transparent'; this.style.color='var(--muted-2)';"
                            title="Editar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="delete(<?php echo e($annotation->id); ?>)"
                            wire:confirm="Excluir esta anotação? Essa ação não pode ser desfeita."
                            class="p-1.5 rounded-lg transition-all"
                            style="color:var(--muted-2);"
                            onmouseover="this.style.background='rgba(239,68,68,.15)'; this.style.color='#fca5a5';"
                            onmouseout="this.style.background='transparent'; this.style.color='var(--muted-2)';"
                            title="Excluir" aria-label="Excluir anotação">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="prose-pm text-sm leading-relaxed">
                    <?php echo $converter->convert($annotation->content)->getContent(); ?>

                </div>

                <div class="flex items-center justify-between mt-3 pt-3 border-t" style="border-color:var(--border);">
                    <span class="text-xs" style="color:var(--muted-3);"><?php echo e($annotation->created_at->diffForHumans()); ?></span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($annotation->pinned): ?>
                    <span class="text-xs font-medium" style="color:#818cf8;">Fixada</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="rounded-2xl border border-dashed py-14 text-center" style="border-color:var(--border-2);">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-3"
                style="background:rgba(99,102,241,.1); border:1px solid rgba(99,102,241,.2);">
                <svg class="w-5 h-5" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-white mb-1">Nenhuma anotacao ainda</p>
            <p class="text-xs" style="color:var(--muted-2);">Clique em "Adicionar Nota" para comecar a escrever.</p>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

</div>
<?php /**PATH /var/www/resources/views/livewire/annotation-editor.blade.php ENDPATH**/ ?>