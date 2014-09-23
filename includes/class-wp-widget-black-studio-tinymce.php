<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget class
 *
 * @package Black_Studio_TinyMCE_Widget
 * @since 0.5
 */

if ( ! class_exists( 'WP_Widget_Black_Studio_TinyMCE' ) ) {

	class WP_Widget_Black_Studio_TinyMCE extends WP_Widget {

		/**
		 * Widget Class constructor
		 *
		 * @uses WP_Widget::__construct()
		 * @return void
		 * @since 0.5
		 */
		public function __construct() {
			/* translators: title of the widget */
			$widget_title = __( 'Visual Editor', 'black-studio-tinymce-widget' );
			/* translators: description of the widget, shown in available widgets */
			$widget_description = __( 'Arbitrary text or HTML with visual editor', 'black-studio-tinymce-widget' );
			$widget_ops = array( 'classname' => 'widget_black_studio_tinymce', 'description' => $widget_description );
			$control_ops = array( 'width' => 800, 'height' => 600 );
			parent::__construct( 'black-studio-tinymce', $widget_title, $widget_ops, $control_ops );
		}

		/**
		 * Output widget HTML code
		 *
		 * @uses apply_filters()
		 * @uses wp_kses_post()
		 * @uses WP_Widget::$id_base
		 *
		 * @param string[] $args
		 * @param mixed[] $instance
		 * @return void
		 * @since 0.5
		 */
		public function widget( $args, $instance ) {
			$before_widget = $args['before_widget'];
			$after_widget = $args['after_widget'];
			$before_title = $args['before_title'];
			$after_title = $args['after_title'];
			$before_text = apply_filters( 'black_studio_tinymce_before_text', '<div class="textwidget">', $instance );
			$after_text = apply_filters( 'black_studio_tinymce_after_text', '</div>', $instance );
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
			$text = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance, $this );
			$output = $before_widget;
			if ( ! empty( $title ) ) {
				$output .= $before_title . $title . $after_title;
			}
			$output .= $before_text . $text . $after_text;
			$output .= $after_widget;
			$allowed_html = wp_kses_allowed_html( 'post' );
			// For themes not supporting HTML5 the style tag should be added to the allowed html tags
			if ( ! current_theme_supports( 'html5', 'gallery' ) ) {
				$allowed_html = array_merge( $allowed_html, array( 'style' => array( 'type' => true ) ) ); 
			}
			echo wp_kses( $output, $allowed_html );
		}

		/**
		 * Update widget data
		 *
		 * @uses current_user_can()
		 * @uses wp_filter_post_kses()
		 * @uses apply_filters()
		 *
		 * @param mixed[] $new_instance
		 * @param mixed[] $old_instance
		 * @return mixed[]
		 * @since 0.5
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			if ( current_user_can( 'unfiltered_html' ) ) {
				$instance['text'] = $new_instance['text'];
			}
			else {
				$instance['text'] = stripslashes( wp_filter_post_kses( addslashes( $new_instance['text'] ) ) ); // wp_filter_post_kses() expects slashed
			}
			$instance['type'] = strip_tags( $new_instance['type'] );
			$instance = apply_filters( 'black_studio_tinymce_widget_update',  $instance, $this );
			return $instance;
		}

		/**
		 * Output widget form
		 *
		 * @uses wp_parse_args()
		 * @uses apply_filters()
		 * @uses esc_attr()
		 * @uses esc_textarea()
		 * @uses WP_Widget::get_field_id()
		 * @uses WP_Widget::get_field_name()
		 * @uses _e()
		 * @uses do_action()
		 * @uses apply_filters()
		 *
		 * @param mixed[] $instance
		 * @return void
		 * @since 0.5
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '', 'type' => 'visual' ) );
			$title = strip_tags( $instance['title'] );
			do_action( 'black_studio_tinymce_before_editor' );
			?>
			<input id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="hidden" value="<?php echo esc_attr( $instance['type'] ); ?>" />
			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
			<?php
			do_action( 'black_studio_tinymce_editor', $instance['text'], $this->get_field_id( 'text' ), $this->get_field_name( 'text' ), $instance['type'] );
			do_action( 'black_studio_tinymce_after_editor' );
		}

	} // END class WP_Widget_Black_Studio_TinyMCE

} // class_exists check
