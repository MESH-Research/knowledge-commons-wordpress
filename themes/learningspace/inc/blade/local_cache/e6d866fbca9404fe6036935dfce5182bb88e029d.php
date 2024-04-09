<div class = "">
    <ul class="<?php echo e(title); ?> widget-post-list">
        <h4 class="label"><?php echo e($title); ?></h4>
        <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><a href="<?php echo e($item['link']); ?>"><?php echo e($item['title']); ?></a></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php /**PATH /var/www/html/wp-content/themes/learningspace/inc/blade/views//widgets/lists.blade.php ENDPATH**/ ?>