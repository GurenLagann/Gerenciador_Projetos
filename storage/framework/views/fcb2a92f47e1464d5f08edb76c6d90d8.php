<?php $__env->startSection('title', 'Ideias'); ?>
<?php $__env->startSection('breadcrumb', 'Ideias'); ?>
<?php $__env->startSection('content'); ?>
<?php
$columns = ['raw', 'exploring', 'validated', 'parked'];
$columnMeta = [
    'raw'       => ['label'=>'Bruto',      'color'=>'#94a3b8','border'=>'rgba(148,163,184,.2)','header_bg'=>'rgba(148,163,184,.07)'],
    'exploring' => ['label'=>'Explorando', 'color'=>'#60a5fa','border'=>'rgba(96,165,250,.25)','header_bg'=>'rgba(96,165,250,.07)'],
    'validated' => ['label'=>'Validado',   'color'=>'#6ee7b7','border'=>'rgba(110,231,183,.25)','header_bg'=>'rgba(110,231,183,.07)'],
    'parked'    => ['label'=>'Pausado',    'color'=>'#fcd34d','border'=>'rgba(252,211,77,.22)', 'header_bg'=>'rgba(252,211,77,.07)'],
];
$priorityMeta = [
    'low'    => ['dot'=>'#4e6080','title'=>'Baixa'],
    'medium' => ['dot'=>'#f59e0b','title'=>'Media'],
    'high'   => ['dot'=>'#ef4444','title'=>'Alta'],
];
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-white tracking-tight">Ideias</h1>
            <p class="text-sm mt-1" style="color:var(--muted-1);">Quadro Kanban para capturar e organizar suas ideias.</p>
        </div>
        <span class="text-xs px-3 py-1.5 rounded-lg border font-medium"
            style="background:var(--surface); border-color:var(--border); color:var(--muted-1);">
            <?php echo e($ideas->flatten()->count()); ?> <?php echo e($ideas->flatten()->count() === 1 ? 'ideia' : 'ideias'); ?>

        </span>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4" style="min-height:72vh;">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $cm = $columnMeta[$column];
            $columnIdeas = $ideas->get($column, collect());
        ?>
        <div class="rounded-2xl flex flex-col border overflow-hidden"
            style="background:var(--surface); border-color:<?php echo e($cm['border']); ?>;">

            
            <div class="px-4 pt-4 pb-3" style="background:<?php echo e($cm['header_bg']); ?>;">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" style="background:<?php echo e($cm['color']); ?>;"></span>
                        <h3 class="text-xs font-bold uppercase tracking-widest" style="color:<?php echo e($cm['color']); ?>;">
                            <?php echo e($cm['label']); ?>

                        </h3>
                    </div>
                    <span class="text-xs font-semibold w-5 h-5 flex items-center justify-center rounded-md"
                        style="background:rgba(255,255,255,.07); color:var(--muted-1);">
                        <?php echo e($columnIdeas->count()); ?>

                    </span>
                </div>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($column === 'raw'): ?>
            <div class="px-3 py-2">
                <form method="POST" action="<?php echo e(route('ideas.store')); ?>" x-data="{open:false}">
                    <?php echo csrf_field(); ?>
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
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <div class="flex-1 px-3 pb-3 space-y-2 overflow-y-auto">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $columnIdeas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idea): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $pm = $priorityMeta[$idea->priority] ?? $priorityMeta['medium']; ?>
                <div class="rounded-xl border group"
                    style="background:var(--surface-2); border-color:var(--border);">
                    <div class="p-3">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <a href="<?php echo e(route('ideas.show', $idea)); ?>"
                                class="text-sm font-medium leading-snug flex-1 hover:text-indigo-300 transition-colors"
                                style="color:#e2e8f0;"><?php echo e($idea->title); ?></a>
                            <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1"
                                style="background:<?php echo e($pm['dot']); ?>;" title="<?php echo e($pm['title']); ?>"></span>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($idea->description): ?>
                        <p class="text-xs leading-relaxed line-clamp-2" style="color:var(--muted-1);"><?php echo e($idea->description); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="flex items-center gap-0.5 px-2 pb-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($column !== 'parked'): ?>
                        <form method="POST" action="<?php echo e(route('ideas.convert', $idea)); ?>">
                            <?php echo csrf_field(); ?>
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
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <form method="POST" action="<?php echo e(route('ideas.destroy', $idea)); ?>" class="ml-auto">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button type="submit"
                                class="text-xs px-2 py-1 rounded-lg transition-colors"
                                style="color:var(--muted-2);"
                                onmouseover="this.style.background='rgba(239,68,68,.15)'; this.style.color='#fca5a5';"
                                onmouseout="this.style.background='transparent'; this.style.color='var(--muted-2)';"
                                onclick="return confirm('Excluir esta ideia?')">Excluir</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($columnIdeas->isEmpty()): ?>
                <div class="text-center py-8" style="color:var(--muted-3);">
                    <p class="text-xs">Nenhuma ideia aqui</p>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/ideas/index.blade.php ENDPATH**/ ?>