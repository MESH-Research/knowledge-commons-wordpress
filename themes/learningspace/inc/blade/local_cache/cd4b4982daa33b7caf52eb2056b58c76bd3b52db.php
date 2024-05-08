<?php if($title): ?>
<h4 class="label">Course name</h4>
<h1 class="course-card--course-name">
    <?php echo e($title); ?>

</h1>
<?php endif; ?>
<h4 class="label">Instructor</h4>
<h2 class="course-card--instructor-name">
	<?php if($name): ?>
		<?php echo e($name); ?>

	<?php else: ?>
		<small>Add instructor name in <a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">Theme Customizer</a></small>
	<?php endif; ?>
</h2>
<p class="course-card--instructor-email">
	<?php if($email): ?>
		<?php echo e($email); ?>

	<?php else: ?>
	<?php endif; ?>
</p>
