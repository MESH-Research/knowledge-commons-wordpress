<div class = "">
    <h3></h3>
    <ul class="<?php echo e(title); ?>">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><p><a href="<?php echo e($item->link); ?>"><?php echo e($item->title); ?></a><?php echo e($item->excerpt); ?></p></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php /**PATH /var/www/html/wp-content/themes/learningspace/inc/blade/views/excerpt_lists.blade.php ENDPATH**/ ?>