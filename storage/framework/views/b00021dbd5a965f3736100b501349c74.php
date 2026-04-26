<?php $__env->startSection('title', 'Projetos'); ?>
<?php $__env->startSection('breadcrumb', 'Projetos'); ?>
<?php $__env->startSection('content'); ?>
<div class="space-y-6 max-w-7xl">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-white tracking-tight">Projetos</h1>
            <p class="text-sm mt-1" style="color:var(--muted-1);">
                <?php echo e($projects->total()); ?> <?php echo e($projects->total() === 1 ? 'projeto' : 'projetos'); ?> no total
            </p>
        </div>
        <form method="POST" action="<?php echo e(route('projects.scan')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition-all hover:opacity-90"
                style="background:linear-gradient(135deg,#059669,#0d9488); box-shadow:0 2px 8px rgba(5,150,105,.25);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Escanear Projetos
            </button>
        </form>
    </div>

    
    <div class="flex items-center gap-2 flex-wrap">
        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center rounded-xl border overflow-hidden"
                style="background:var(--surface); border-color:var(--border);">
                <label class="px-3 text-xs font-semibold" style="color:var(--muted-2);">Status</label>
                <select name="status" onchange="this.form.submit()"
                    class="bg-transparent border-0 text-sm py-2 pr-3 focus:ring-0 focus:outline-none cursor-pointer"
                    style="color:var(--text);">
                    <option value="">Todos</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($s); ?>" <?php if(request('status') === $s): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('-', ' ', $s))); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>
            <div class="flex items-center rounded-xl border overflow-hidden"
                style="background:var(--surface); border-color:var(--border);">
                <label class="px-3 text-xs font-semibold" style="color:var(--muted-2);">Ordem</label>
                <select name="sort" onchange="this.form.submit()"
                    class="bg-transparent border-0 text-sm py-2 pr-3 focus:ring-0 focus:outline-none cursor-pointer"
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
        <p class="text-xs" style="color:var(--muted-1);">Use o botao acima para escanear e detectar seus projetos.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo $__env->make('projects._card', ['project' => $project], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    <div class="mt-2"><?php echo e($projects->links()); ?></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/projects/index.blade.php ENDPATH**/ ?>