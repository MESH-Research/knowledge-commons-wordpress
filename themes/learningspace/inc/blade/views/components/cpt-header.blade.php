<?php
global $post;
$author_id = $post->post_author;
?>
<h4 class="label text-light"><?php echo get_post_type(); ?></h4>
<h1><?php the_title(); ?></h1>

<h4 class="label text-light">Author</h4>
<h2 class="text-light"><?php echo get_the_author_meta('first_name', $author_id).' '.get_the_author_meta('last_name', $author_id) ?></h2>
