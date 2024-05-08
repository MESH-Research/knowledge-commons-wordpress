<div class = "">
    <ul class="<?php echo e($title); ?> widget-post-list--excerpt">
        <h4 class="label"><?php echo e($title); ?></h4>
        <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><h2><a href="<?php echo e($item['link']); ?>"><?php echo e($item['title']); ?></a></h2><div class="excerpt_list_item"><?php echo e($item['excerpt']); ?></div></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
