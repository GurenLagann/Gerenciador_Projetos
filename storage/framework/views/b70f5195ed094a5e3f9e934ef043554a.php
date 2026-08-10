<?php
$sm = $project->status;
$git = $project->git_info;
$lastCommit = $git['commits'][0] ?? null;
?>

<div class="card-hover rounded-2xl overflow-hidden group"
    style="background:var(--surface); border:1px solid var(--border);">

    
    <div class="h-0.5 w-full" style="background:<?php echo e($sm->bar); ?>; opacity:.7;"></div>

    <div class="p-5">
        
        <div class="flex items-start justify-between gap-2 mb-3">
            <a href="<?php echo e(route('projects.show', $project)); ?>" class="flex-1 min-w-0">
                <h4 class="font-semibold text-white leading-snug group-hover:text-indigo-300 transition-colors"><?php echo e($project->name); ?></h4>
            </a>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <span class="text-xs px-2 py-0.5 rounded-md font-medium whitespace-nowrap"
                    style="background:<?php echo e($sm->bg); ?>; color:<?php echo e($sm->color); ?>;"><?php echo e($sm->label); ?></span>
                <form method="POST" action="<?php echo e(route('projects.destroy', $project)); ?>"
                    onsubmit="return confirm('Remover este projeto do painel? Ele será ignorado em futuros scans.')">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button type="submit" aria-label="Remover projeto"
                        class="w-5 h-5 rounded flex items-center justify-center transition-colors"
                        style="color:var(--muted-2);"
                        onmouseover="this.style.background='rgba(239,68,68,.15)'; this.style.color='#fca5a5';"
                        onmouseout="this.style.background='transparent'; this.style.color='var(--muted-2)';">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <a href="<?php echo e(route('projects.show', $project)); ?>" class="block">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($project->description): ?>
            <p class="text-xs leading-relaxed mb-3 line-clamp-2" style="color:var(--muted-1);"><?php echo e($project->description); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($project->tech_stack)): ?>
            <div class="flex flex-wrap gap-1.5 mb-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = array_slice($project->tech_stack, 0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tech): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="text-xs px-1.5 py-0.5 rounded font-medium"
                    style="background:rgba(99,102,241,.13); color:#a5b4fc;"><?php echo e($tech); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($project->tech_stack) > 4): ?>
                <span class="text-xs px-1.5 py-0.5 rounded" style="color:var(--muted-2);">
                    +<?php echo e(count($project->tech_stack) - 4); ?>

                </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <div class="mb-4">
                <div class="flex justify-between items-center text-xs mb-1.5" style="color:var(--muted-2);">
                    <span>Progresso</span>
                    <span class="font-semibold tabular-nums"
                        style="color:<?php echo e($project->progress >= 100 ? '#6ee7b7' : '#94a3b8'); ?>;"><?php echo e($project->progress); ?>%</span>
                </div>
                <div class="w-full h-1.5 rounded-full" style="background:var(--border-2);">
                    <div class="h-1.5 rounded-full transition-all"
                        style="width:<?php echo e($project->progress); ?>%; background:<?php echo e($sm->bar); ?>;"></div>
                </div>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($lastCommit): ?>
            <div class="pt-3 border-t" style="border-color:var(--border);">
                <div class="flex items-center gap-1.5 mb-1">
                    <svg class="w-3 h-3 flex-shrink-0" style="color:var(--muted-2);" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.93 8.5a4.002 4.002 0 0 1-7.86 0H.75a.75.75 0 0 1 0-1.5h3.32a4.002 4.002 0 0 1 7.86 0h3.32a.75.75 0 0 1 0 1.5zm-1.43-.75a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0z"/>
                    </svg>
                    <span class="text-xs font-mono" style="color:#818cf8;"><?php echo e($lastCommit['hash']); ?></span>
                    <span style="color:var(--border-3);">·</span>
                    <span class="text-xs" style="color:var(--muted-2);"><?php echo e($git['branch']); ?></span>
                </div>
                <p class="text-xs truncate" style="color:var(--muted-1);"><?php echo e($lastCommit['message']); ?></p>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </a>
    </div>
</div>
<?php /**PATH /var/www/resources/views/projects/_card.blade.php ENDPATH**/ ?>