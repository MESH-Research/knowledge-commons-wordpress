@if ($title)
<h4 class="label">Course name</h4>
<h1 class="course-card--course-name">
    {{ $title }}
</h1>
@endif
<h4 class="label">Instructor</h4>
<h2 class="course-card--instructor-name">
	@if ($name)
		{{ $name }}
	@else
		<small>Add instructor name in <a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">Theme Customizer</a></small>
	@endif
</h2>
<p class="course-card--instructor-email">
	@if ($email)
		{{ $email }}
	@else
	@endif
</p>
