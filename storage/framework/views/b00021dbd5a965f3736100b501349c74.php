<?php $__env->startSection('title', 'Projetos'); ?>
<?php $__env->startSection('breadcrumb', 'Projetos'); ?>
<?php $__env->startSection('content'); ?>
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-white tracking-tight">Projetos</h1>
            <p class="text-sm mt-1" style="color:var(--muted-1);">
                <?php echo e($projects->total()); ?> <?php echo e($projects->total() === 1 ? 'projeto' : 'projetos'); ?> no total
            </p>
        </div>
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('scanner-status', []);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1310764041-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    </div>

    
    <div class="flex items-center gap-2 flex-wrap">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
            <div class="flex items-center rounded-xl border overflow-hidden w-full sm:w-auto"
                style="background:var(--surface); border-color:var(--border);">
                <label class="px-3 text-xs font-semibold flex-shrink-0" style="color:var(--muted-2);">Status</label>
                <select name="status" onchange="this.form.submit()"
                    class="bg-transparent border-0 text-sm py-2.5 sm:py-2 pr-3 focus:ring-0 focus:outline-none cursor-pointer w-full"
                    style="color:var(--text);">
                    <option value="">Todos</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($s->code); ?>" <?php if(request('status') === $s->code): echo 'selected'; endif; ?>><?php echo e($s->label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>
            <div class="flex items-center rounded-xl border overflow-hidden w-full sm:w-auto"
                style="background:var(--surface); border-color:var(--border);">
                <label class="px-3 text-xs font-semibold flex-shrink-0" style="color:var(--muted-2);">Ordem</label>
                <select name="sort" onchange="this.form.submit()"
                    class="bg-transparent border-0 text-sm py-2.5 sm:py-2 pr-3 focus:ring-0 focus:outline-none cursor-pointer w-full"
                    style="color:var(--text);">
                    <option value="latest"   <?php if(request('sort','latest')==='latest'): echo 'selected'; endif; ?>>Mais Recente</option>
                    <option value="name"     <?php if(request('sort')==='name'): echo 'selected'; endif; ?>>Nome</option>
                    <option value="progress" <?php if(request('sort')==='progress'): echo 'selected'; endif; ?>>Progresso</option>
                    <option value="status"   <?php if(request('sort')==='status'): echo 'selected'; endif; ?>>Status</option>
                </select>
            </div>
        </form>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($projects->isEmpty()): ?>
    <div class="rounded-2xl border border-dashed py-20 text-center" style="border-color:var(--border-2);">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
            style="background:rgba(99,102,241,.1); border:1px solid rgba(99,102,241,.2);">
            <svg class="w-7 h-7" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-white mb-1">Nenhum projeto encontrado</p>
        <p class="text-xs" style="color:var(--muted-1);">Use o botão acima para escanear e detectar seus projetos.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo $__env->make('projects._card', ['project' => $project], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    <div class="mt-2"><?php echo e($projects->links()); ?></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/projects/index.blade.php ENDPATH**/ ?>