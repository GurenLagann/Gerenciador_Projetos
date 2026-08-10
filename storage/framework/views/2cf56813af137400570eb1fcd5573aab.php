<?php $__env->startSection('title', $project->name); ?>
<?php $__env->startSection('breadcrumb'); ?>
<span style="color:var(--muted-2);">Projetos</span>
<span class="mx-1.5" style="color:var(--border-3);">/</span>
<span class="text-white"><?php echo e($project->name); ?></span>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<?php
$sm = $project->status;
$git = $project->git_info;
?>

<div class="space-y-6 max-w-7xl">

    
    <div class="rounded-2xl border overflow-hidden" style="background:var(--surface); border-color:var(--border);">
        <div class="h-0.5" style="background:<?php echo e($sm->bar); ?>;"></div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row items-start gap-5">
                <div class="flex-1 min-w-0">
                    <a href="<?php echo e(route('projects.index')); ?>"
                        class="inline-flex items-center gap-1.5 text-xs mb-3 hover:opacity-80 transition-opacity"
                        style="color:var(--muted-1);">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Projetos
                    </a>
                    <h1 class="text-2xl font-bold text-white tracking-tight mb-2"><?php echo e($project->name); ?></h1>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($project->description): ?>
                    <p class="text-sm leading-relaxed mb-4" style="color:var(--muted-1);"><?php echo e($project->description); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($project->tech_stack)): ?>
                    <div class="flex flex-wrap gap-1.5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $project->tech_stack; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tech): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="text-xs px-2 py-0.5 rounded font-medium"
                            style="background:rgba(99,102,241,.13); color:#a5b4fc;"><?php echo e($tech); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex flex-col sm:items-end gap-3 flex-shrink-0 w-full sm:w-auto">
                    <form method="POST" action="<?php echo e(route('projects.status', $project)); ?>" class="w-full sm:w-auto">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <select name="status" onchange="this.form.submit()"
                            class="text-sm rounded-xl border px-3 py-2 font-medium focus:ring-0 focus:outline-none w-full sm:w-auto"
                            style="background:var(--surface-2); border-color:var(--border-2); color:<?php echo e($sm->color); ?>;">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s->code); ?>" <?php if($project->status_id === $s->id): echo 'selected'; endif; ?>
                                style="color:<?php echo e($s->color); ?>;"><?php echo e($s->label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </form>
                    <form method="POST" action="<?php echo e(route('projects.destroy', $project)); ?>"
                        onsubmit="return confirm('Remover este projeto do painel? Ele será ignorado em futuros scans.')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="text-xs px-3 py-2 rounded-xl border font-medium transition-colors w-full"
                            style="border-color:var(--border-2); color:var(--muted-1);"
                            onmouseover="this.style.background='rgba(239,68,68,.12)'; this.style.color='#fca5a5'; this.style.borderColor='rgba(239,68,68,.3)';"
                            onmouseout="this.style.background='transparent'; this.style.color='var(--muted-1)'; this.style.borderColor='var(--border-2)';">
                            Remover projeto
                        </button>
                    </form>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($git): ?>
                    <div class="flex items-center gap-2 text-xs" style="color:var(--muted-2);">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span><?php echo e($git['branch']); ?></span>
                        <span style="color:var(--border-3);">·</span>
                        <span><?php echo e(number_format($git['total_commits'])); ?> commits</span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <div class="mt-6 pt-5 border-t" style="border-color:var(--border);"
                x-data="{ progress: <?php echo e($project->progress); ?> }">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium" style="color:var(--muted-2);">Progresso</span>
                    <span class="text-xs font-semibold tabular-nums"
                        :style="progress >= 100 ? 'color:#6ee7b7;' : 'color:#94a3b8;'"
                        x-text="progress + '%'"></span>
                </div>
                <input type="range" min="0" max="100" x-model="progress" class="w-full"
                    @change="fetch('<?php echo e(route('projects.progress', $project)); ?>', {
                        method:'PATCH',
                        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'<?php echo e(csrf_token()); ?>'},
                        body:JSON.stringify({progress:progress})
                    })">
                <div class="flex justify-between text-xs mt-1.5" style="color:var(--muted-3);">
                    <span>0%</span><span>50%</span><span>100%</span>
                </div>
            </div>
        </div>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        
        <div class="col-span-2">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('annotation-editor', ['annotatableType' => 'project','annotatableId' => $project->id]);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2519850238-0', $__key);

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

        
        <div class="rounded-2xl border p-5" style="background:var(--surface); border-color:var(--border);">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                    <svg class="w-4 h-4" style="color:#6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Marcos
                </h3>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($project->milestones->count() > 0): ?>
                <span class="text-xs font-medium tabular-nums px-2 py-0.5 rounded-md"
                    style="background:var(--surface-2); color:var(--muted-1);">
                    <?php echo e($project->milestones->where('completed',true)->count()); ?>/<?php echo e($project->milestones->count()); ?>

                </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <form method="POST" action="<?php echo e(route('milestones.store', $project)); ?>" class="mb-4"
                x-data="{open:false}">
                <?php echo csrf_field(); ?>
                <div x-show="!open"
                    @click="open=true;$nextTick(()=>$el.nextElementSibling.querySelector('input').focus())"
                    class="text-xs text-center py-2.5 rounded-xl border border-dashed cursor-pointer transition-all"
                    style="color:var(--muted-2); border-color:var(--border-2);"
                    onmouseover="this.style.borderColor='#6366f1'; this.style.color='#a5b4fc';"
                    onmouseout="this.style.borderColor='var(--border-2)'; this.style.color='var(--muted-2)';">
                    + Novo marco
                </div>
                <div x-show="open" x-cloak class="space-y-2">
                    <input type="text" name="title" placeholder="Titulo do marco..."
                        class="w-full text-sm rounded-xl px-3 py-2 border focus:outline-none transition-colors focus:border-indigo-500"
                        style="background:var(--surface-2); border-color:var(--border-2); color:#e2e8f0;">
                    <div class="flex gap-1.5">
                        <button type="submit" class="flex-1 text-xs py-2 rounded-lg font-medium text-white"
                            style="background:#4f46e5;">Salvar</button>
                        <button type="button" @click="open=false"
                            class="flex-1 text-xs py-2 rounded-lg font-medium"
                            style="background:var(--border); color:var(--muted-1);">Cancelar</button>
                    </div>
                </div>
            </form>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($project->milestones->count() > 0): ?>
            <div class="mb-3">
                <div class="w-full h-1 rounded-full" style="background:var(--border-2);">
                    <div class="h-1 rounded-full transition-all" style="width:<?php echo e($project->milestone_progress); ?>%; background:#6366f1;"></div>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="space-y-0.5">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $project->milestones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $milestone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex items-center gap-2.5 group px-2 py-2 rounded-xl hover:bg-white/[.03] transition-colors">
                    <form method="POST" action="<?php echo e(route('milestones.toggle', [$project, $milestone])); ?>">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <button type="submit"
                            class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                            style="<?php echo e($milestone->completed
                                ? 'border-color:#6366f1; background:#6366f1;'
                                : 'border-color:var(--border-3);'); ?>">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($milestone->completed): ?>
                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </button>
                    </form>
                    <span class="flex-1 text-sm leading-snug"
                        style="<?php echo e($milestone->completed ? 'color:var(--muted-2); text-decoration:line-through;' : 'color:#e2e8f0;'); ?>">
                        <?php echo e($milestone->title); ?>

                    </span>
                    <form method="POST" action="<?php echo e(route('milestones.destroy', [$project, $milestone])); ?>"
                        onsubmit="return confirm('Excluir este marco?')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit"
                            class="w-5 h-5 rounded flex items-center justify-center text-sm transition-colors hover:bg-red-900/30"
                            style="color:var(--muted-2);" aria-label="Excluir marco">&times;</button>
                    </form>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center py-6" style="color:var(--muted-3);">
                    <p class="text-xs">Nenhum marco ainda.</p>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($git && !empty($git['commits'])): ?>
    <div class="rounded-2xl border overflow-hidden" style="background:var(--surface); border-color:var(--border);">
        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--border);">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4" style="color:#6366f1;" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.93 8.5a4.002 4.002 0 0 1-7.86 0H.75a.75.75 0 0 1 0-1.5h3.32a4.002 4.002 0 0 1 7.86 0h3.32a.75.75 0 0 1 0 1.5zm-1.43-.75a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0z"/>
                </svg>
                Historico Git
            </h3>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5" style="color:var(--muted-1);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <?php echo e($git['branch']); ?>

                </span>
                <span class="px-2 py-0.5 rounded-md font-medium" style="background:var(--surface-2); color:var(--muted-1);">
                    <?php echo e(number_format($git['total_commits'])); ?> commits
                </span>
            </div>
        </div>
        <div style="divide-color:var(--border);">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $git['commits']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $commit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $date = new \DateTime($commit['date']);
                $diff = (new \DateTime())->diff($date);
                if ($diff->days === 0) $ago = 'hoje';
                elseif ($diff->days === 1) $ago = 'ontem';
                elseif ($diff->days < 7) $ago = $diff->days . 'd atras';
                elseif ($diff->days < 30) $ago = floor($diff->days/7) . 'sem atras';
                elseif ($diff->days < 365) $ago = floor($diff->days/30) . 'm atras';
                else $ago = floor($diff->days/365) . 'a atras';
            ?>
            <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/[.02] transition-colors <?php echo e($i > 0 ? 'border-t' : ''); ?>"
                style="<?php echo e($i > 0 ? 'border-color:var(--border);' : ''); ?>">
                <code class="text-xs font-mono flex-shrink-0 px-2 py-0.5 rounded"
                    style="background:rgba(99,102,241,.12); color:#818cf8;"><?php echo e($commit['hash']); ?></code>
                <p class="flex-1 text-sm truncate" style="color:#e2e8f0; opacity:.85;"><?php echo e($commit['message']); ?></p>
                <span class="text-xs flex-shrink-0" style="color:var(--muted-2);"><?php echo e($commit['author']); ?></span>
                <span class="text-xs flex-shrink-0 tabular-nums" style="color:var(--muted-3);"><?php echo e($ago); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="flex items-center gap-5 text-xs pb-2" style="color:var(--muted-3);">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($project->path): ?>
        <span class="flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <?php echo e($project->path); ?>

        </span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($project->last_scanned_at): ?>
        <span class="flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Escaneado <?php echo e($project->last_scanned_at->diffForHumans()); ?>

        </span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/projects/show.blade.php ENDPATH**/ ?>