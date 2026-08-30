<?php $__env->startSection('title', $idea->title); ?>
<?php $__env->startSection('breadcrumb'); ?>
<span style="color:var(--muted-2);">Ideias</span>
<span class="mx-1.5" style="color:var(--border-3);">/</span>
<span class="text-white"><?php echo e(Str::limit($idea->title, 40)); ?></span>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<?php
$sm = $idea->status;
$pm = $idea->priority;
?>

<div class="space-y-6 max-w-6xl">

    <div class="rounded-2xl border p-6" style="background:var(--surface); border-color:var(--border);">
        <div class="flex flex-col sm:flex-row items-start gap-5">
            <div class="flex-1 min-w-0">
                <a href="<?php echo e(route('ideas.index')); ?>"
                    class="inline-flex items-center gap-1.5 text-xs mb-3 hover:opacity-80 transition-opacity"
                    style="color:var(--muted-1);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Ideias
                </a>
                <h1 class="text-2xl font-bold text-white tracking-tight mb-2"><?php echo e($idea->title); ?></h1>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($idea->description): ?>
                <p class="text-sm leading-relaxed mb-4" style="color:var(--muted-1);"><?php echo e($idea->description); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2.5 py-1 rounded-lg font-medium"
                        style="background:<?php echo e($sm->bg); ?>; color:<?php echo e($sm->color); ?>;"><?php echo e($sm->label); ?></span>
                    <span class="text-xs flex items-center gap-1.5" style="color:var(--muted-1);">
                        <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo e($pm->dot); ?>;"></span>
                        Prioridade <?php echo e($pm->label); ?>

                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:items-end gap-3 flex-shrink-0 w-full sm:w-auto">
                <form method="POST" action="<?php echo e(route('ideas.update', $idea)); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <select name="status" onchange="this.form.submit()"
                        class="text-sm rounded-xl border px-3 py-2 focus:ring-0 focus:outline-none"
                        style="background:var(--surface-2); border-color:var(--border-2); color:var(--text);">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s->code); ?>" <?php if($idea->status_id === $s->id): echo 'selected'; endif; ?>
                            style="color:<?php echo e($s->color); ?>;"><?php echo e($s->label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </form>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($idea->status->code !== 'converted'): ?>
                <form method="POST" action="<?php echo e(route('ideas.convert', $idea)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition-all hover:opacity-90"
                        style="background:linear-gradient(135deg,#059669,#0d9488); box-shadow:0 2px 8px rgba(5,150,105,.25);">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        Converter em Projeto
                    </button>
                </form>
                <?php elseif($idea->convertedProject): ?>
                <a href="<?php echo e(route('projects.show', $idea->convertedProject)); ?>"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-opacity hover:opacity-80"
                    style="background:rgba(196,181,253,.1); border:1px solid rgba(196,181,253,.2); color:#c4b5fd;">
                    Ver Projeto
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <?php else: ?>
                <span class="text-sm" style="color:var(--muted-3);">Projeto convertido foi removido</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('annotation-editor', ['annotatableType' => 'idea','annotatableId' => $idea->id]);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-606699032-0', $__key);

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/ideas/show.blade.php ENDPATH**/ ?>