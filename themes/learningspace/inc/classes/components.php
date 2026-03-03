<?php

namespace learningspace\inc\classes;


class components extends \WP_Widget {
	public function __construct( $classname = '', $desc = '', array $widget_details = [] ) {
		$widget_details = array_merge( array(
			'classname'   => $classname,
			'description' => $desc
		), $widget_details );
		parent::__construct( $classname, $desc, $widget_details );
	}

	public function form( $instance ) {
		// Field Values
		$title      = ( ! empty( $instance['title'] ) ) ? $instance['title'] : 'Assignment';
		$count      = ( ! empty( $instance['count'] ) ) ? $instance['count'] : 5;
		$orderby    = ( ! empty( $instance['orderby'] ) ) ? $instance['orderby'] : 'post_date';
		$order      = ( ! empty( $instance['order'] ) ) ? $instance['order'] : 'desc';
		$artifactby = ( ! empty( $instance['artifact'] ) ) ? $instance['artifact'] : 'assignments';
		$displayby  = ( ! empty( $instance['display'] ) ) ? $instance['display'] : 'lists';

		// Field Options
		$orderby_options = array(
			'ID'         => 'ID',
			'post_date'  => 'Post Date',
			'post_count' => 'Post Count'
		);

		$order_options = array(
			'DESC' => 'Descending',
			'ASC'  => 'Ascending'
		);

		$artifact_options = array(
			'assignment' => 'Assignments',
			'lesson'     => 'Lessons',
			'document'   => 'Documents'
		);

		$display_options = array(
			'excerpt_lists' => 'Excerpt List',
			'lists'         => 'Link List',
		);
		?>

        <div>
            <p>
                <label for="<?php echo $this->get_field_name( 'title' ); ?>">Title: </label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                       value="<?php echo esc_attr( $title ); ?>"/>
            </p>

            <p>
                <label for="<?php echo $this->get_field_name( 'count' ); ?>">Authors To Show: </label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>"
                       name="<?php echo $this->get_field_name( 'count' ); ?>" type="text"
                       value="<?php echo esc_attr( $count ); ?>"/>
            </p>

            <p>
                <label for="<?php echo $this->get_field_name( 'artifact' ); ?>">Data Source: </label>
                <select class='widefat' id="<?php echo $this->get_field_id( 'artifact' ); ?>"
                        name="<?php echo $this->get_field_name( 'artifact' ); ?>">
					<?php foreach ( $artifact_options as $value => $name ) : ?>
                        <option <?php selected( $artifactby, $value ) ?>
                                value='<?php echo $value ?>'><?php echo $name ?></option>
					<?php endforeach ?>
                </select>
            </p>

            <p>
                <label for="<?php echo $this->get_field_name( 'display' ); ?>">List Display: </label>
                <select class='widefat' id="<?php echo $this->get_field_id( 'display' ); ?>"
                        name="<?php echo $this->get_field_name( 'display' ); ?>">
					<?php foreach ( $display_options as $value => $name ) : ?>
                        <option <?php selected( $displayby, $value ) ?>
                                value='<?php echo $value ?>'><?php echo $name ?></option>
					<?php endforeach ?>
                </select>
            </p>

            <p>
                <label for="<?php echo $this->get_field_name( 'orderby' ); ?>">Order By: </label>
                <select class='widefat' id="<?php echo $this->get_field_id( 'orderby' ); ?>"
                        name="<?php echo $this->get_field_name( 'orderby' ); ?>">
					<?php foreach ( $orderby_options as $value => $name ) : ?>
                        <option <?php selected( $orderby, $value ) ?>
                                value='<?php echo $value ?>'><?php echo $name ?></option>
					<?php endforeach ?>
                </select>
            </p>

            <p>
                <label for="<?php echo $this->get_field_name( 'order' ); ?>">Order: </label>
                <select class='widefat' id="<?php echo $this->get_field_id( 'order' ); ?>"
                        name="<?php echo $this->get_field_name( 'order' ); ?>">
					<?php foreach ( $order_options as $value => $name ) : ?>
                        <option <?php selected( $order, $value ) ?>
                                value='<?php echo $value ?>'><?php echo $name ?></option>
					<?php endforeach ?>
                </select>
            </p>
        </div>
		<?php
	}


	public function widget( $args, $instance ) {
		$file = '/widgets/' . $instance['display'];
		//util::write_log(B_VIEWS."/$file.blade.php");
		//util::write_log(file_exists( B_VIEWS."/$file.blade.php" ));
		if ( file_exists( B_VIEWS . "/$file.blade.php" ) ) {
			$args = array(
				'post_type' => $instance['artifact'],
				'showposts' => $instance['count'],
				'orderby'   => $instance['orderby'],
				'order'     => $instance['order'],
			);

			$the_query = new \WP_Query( $args );
			if ( $the_query->have_posts() ) {
				$data = array();
				$c    = 0;
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$data[ $c ]['title']   = the_title( '', '', false );
					$data[ $c ]['date']    = get_the_date();
					$data[ $c ]['link']    = get_the_permalink();
					$data[ $c ]['excerpt'] = get_the_excerpt();
					$c ++;
				}
				//util::write_log( $instance );
				//util::write_log( $file );
				//util::write_log($data);
				if ( array_key_exists( 'before_widget', $args ) ) {
					echo $args['before_widget'];
				}
				echo blade()->make( $file, [ 'title' => $instance['title'], 'data' => $data ] )->render();
				if ( array_key_exists( 'after_widget', $args ) ) {
					echo $args['after_widget'];
				}
			} else {
				echo blade()->make( "/widgets/lists_none", [ 'title' => $instance['title'] ] )->render();
			}
		} else {
			echo blade()->make( "/widgets/lists_none" )->render();
		}
	}

	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}
}
