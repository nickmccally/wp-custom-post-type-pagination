<?php
/*
Plugin Name: WP Custom Post Type With Pagination
Description: Forked and modified to fit the need of setting custom post type and resolve for bugs that existed in the original plugin.
Author: Nick McCally
Version: 1.0
Author URI: https://nickmccally.com
*/
include 'mq_resizer.php';

class wpRelatedPosts extends WP_Widget {

	public $widget_version;

	public $widget_path;

	public $widget_url;


	function __construct() {
		// Instantiate the parent object
		parent::__construct( false, 'Related Post with pagination' );
		$this->rc_widget_path();
		$this->rc_widget_url();
		$this->rc_widget_version();

		add_action('wp_enqueue_scripts' , array($this, 'add_scripts'));
		add_action( 'wp_ajax_get_recent_post', array($this, 'ajax_paging_post') );
		add_action( 'wp_ajax_nopriv_get_recent_post', array($this, 'ajax_paging_post'));

	}


	public function rc_widget_version(){
		return $this->widget_version = '1.0';
	}


	public function rc_widget_path(){
		return $this->widget_path = plugin_dir_path(__FILE__);
	}


	public function rc_widget_url(){
		return $this->widget_url = plugin_dir_url(__FILE__);
	}

	public function word_limit_content( $content , $length = null){
		$word_limit = ($length)?$length:10;
		$word_limit = ($word_limit >= 10)?$word_limit:10;
		$content = strip_tags($content);
		$content = trim(preg_replace("/Read More.../", "", $content));
		$content = preg_replace("/<img[^>]+\>/i", " ", $content);
		$content = preg_replace( '/\[[^\]]+\]/', '', $content);  # strip shortcodes, keep shortcode content
		$words = explode(' ', $content, ($word_limit + 1));
		if(count($words) > $word_limit){
			array_pop($words);
			$content = implode(' ', $words);
			return esc_attr($content);
		}else{
			return esc_attr($content);
		}
	}

	public function widget( $args, $instance ) {
		// Widget output
		$template = 'related-post.php';
		echo $args['before_widget'];
		$uid = uniqid();

		$companyData = 	get_query_var( 'company_data');
		if(isset($companyData->symbol))
			$symbol = $instance['symbol'] = $companyData->symbol;

		$theme_file = locate_template( array( 'related-post/' . $template ) ) ;
		if(isset($theme_file) && !empty($theme_file)){
			$templateFile = $theme_file;
		}else{
			$templateFile = $this->widget_path.'templates/'.$template;
		}
		// echo get_field('symbol');exit;

		$data = apply_filters( 'rc_repl_template' , $templateFile );

		$postsPerPage = ($instance['number_of_posts'])?$instance['number_of_posts']:5;
		$orderBy = ($instance['order_by'])?$instance['order_by']:'ID';
		$order = ($instance['order'])?$instance['order']:'DESC';
		$category = ($instance['category'])?$instance['category']:'';
		$symbol = ($instance['symbol'])?$instance['symbol']:'';
		$post_type = ($instance['post_type'])?$instance['post_type']:'';
		$is_pagination = ($instance['is_pagination'])?$instance['is_pagination']:'yes';
		$is_thumbail = ($instance['is_thumbail'])?$instance['is_thumbail']:'yes';
		$is_excerpt = ($instance['is_excerpt'])?$instance['is_excerpt']:'yes';
		$is_date = ($instance['is_date'])?$instance['is_date']:'yes';
		$is_author = ($instance['is_author'])?$instance['is_author']:'yes';
		$thumb_height = ($instance['thumb_height'])?$instance['thumb_height']:75;
		$thumb_width = ($instance['thumb_width'])?$instance['thumb_width']:100;
		$content_limit = ($instance['content_limit'])?$instance['content_limit']:10;
		$over_lay = ($instance['over_lay'])?$instance['over_lay']:'';
		$class_suffix = ($instance['class_suffix'])?' '.$instance['class_suffix']:'';

		$Qargs = array(
			'post_status'		=> 'publish',
			'orderby'			=> $orderBy,
			'order'				=> $order,
			'meta_query' => array(
		                    array(
		                        'key' => 'symbol',
		                        'value' => $symbol,

		                    )
		                )
		);

		if(isset($category) && !empty($category)){
			$Qargs['cat'] = $category;
		}
		if(isset($post_type) && !empty($post_type)){
			$Qargs['post_type'] = array_map('trim', explode(',', $post_type));
		}

		$data_post = new WP_Query( $Qargs );

		$published_posts = $data_post->found_posts;


		$totalPage = round($published_posts/$postsPerPage, 0 , PHP_ROUND_HALF_UP);


		$Qargs['posts_per_page'] = $postsPerPage;



		$the_query = new WP_Query( $Qargs );

		$ajaxNonce = wp_create_nonce( 'recent-slider-ajax-nonce' );
		if(isset($is_pagination) && !empty($is_pagination) && $is_pagination == 'yes'):
			wp_localize_script( 'recent-slider', 'paging_'.$uid, array( 'pagination_token' => $ajaxNonce, 'ajax_url' => admin_url( 'admin-ajax.php' ) , 'posts_per_page' => $postsPerPage,'order_by'=>$orderBy,'order'=>$order,'post_type'=>$post_type,'symbol'=>$symbol,'category'=>$category,'is_thumbail'=>$is_thumbail,'is_excerpt'=>$is_excerpt , 'is_date'=>$is_date ,'is_author'=>$is_author, 'thumb_height'=>$thumb_height,'thumb_width'=>$thumb_width,'content_limit'=>$content_limit));
		endif;

		echo '<div class="postWrapper'.esc_attr($class_suffix).'">';
		if ( $the_query->have_posts() ) {

			echo '<div class="latest_posts-wgt"> ';

			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'];
					if(empty($args['before_title'])){
						echo '<h3 class="widgettitle zn-sidebar-widget-title title">';
					}

					echo apply_filters( 'widget_title', $instance['title'] );

					if(empty($args['before_title'])){
						echo '</h3>';
					}
					echo $args['after_title'];
			}
			echo '<div class="recentPostWrapper">';
			echo '<ul class="posts recentPost latest_posts-wgt-posts" id="list'.$uid.'" >';
				while ( $the_query->have_posts() ) {
					$the_query->the_post();

					$content = get_the_excerpt();
					$content = $this->word_limit_content($content , $content_limit);
					include($templateFile);
				}
				echo '</ul>';

				if(isset($over_lay) && !empty($over_lay)){
					$rgba = $over_lay;
				}else{
					$rgba =  'rgba(255, 255, 255, 0.8)';
				}
				if(isset($is_pagination) && !empty($is_pagination) && $is_pagination == 'yes'):
					echo '<div class="ajaxPreLoader" style="background-color:'.$rgba.'"></div>';
				endif;

				echo '</div>';
				echo '</div>';
				if(isset($is_pagination) && !empty($is_pagination) && $is_pagination == 'yes'){
					echo '<div class="sliderPagination" data-currentpage="1" data-totalpage="'.$totalPage.'" data-step="'.$postsPerPage.'" data-uid="'.$uid.'">';
					echo '<a href="javascript:void(0)" class="paginateLink previousLink" data-action="previous">&#8249;</a>';
					echo '<a href="javascript:void(0)" class="paginateLink nextLink" data-action="next">&#8250;</a>';
					echo '</div>';
				}


		}else{
			//Load template for no content
		}
		echo '</div>';

		wp_reset_postdata();
		echo $args['after_widget'];
	}

	public function ajax_paging_post(){

		$token = ($_POST['token'])?sanitize_text_field($_POST['token']):'';

		check_ajax_referer( 'recent-slider-ajax-nonce', 'token' );



		$postsPerPage = ($_POST['posts_per_page'])?sanitize_text_field($_POST['posts_per_page']):5;
		$orderBy = ($_POST['order_by'])?sanitize_text_field($_POST['order_by']):'ID';
		$order = ($_POST['order'])?sanitize_text_field($_POST['order']):'DESC';
		$category = ($_POST['category'])?sanitize_text_field($_POST['category']):'';
		$symbol = ($_POST['symbol'])?sanitize_text_field($_POST['symbol']):'';
		$post_type = ($_POST['post_type'])?sanitize_text_field($_POST['post_type']):'';
		$is_pagination = ($_POST['is_pagination'])?sanitize_text_field($_POST['is_pagination']):'yes';
		$is_thumbail = ($_POST['is_thumbail'])?sanitize_text_field($_POST['is_thumbail']):'yes';
		$is_excerpt = ($_POST['is_excerpt'])?sanitize_text_field($_POST['is_excerpt']):'yes';
		$is_date = ($_POST['is_date'])?sanitize_text_field($_POST['is_date']):'yes';
		$is_author = ($_POST['is_author'])?sanitize_text_field($_POST['is_author']):'yes';
		$thumb_height = ($_POST['thumb_height'])?sanitize_text_field($_POST['thumb_height']):75;
		$thumb_width = ($_POST['thumb_width'])?sanitize_text_field($_POST['thumb_width']):100;
		$content_limit = ($_POST['content_limit'])?sanitize_text_field($_POST['content_limit']):10;

		$Qargs = array(
			'posts_per_page'	=> $postsPerPage,
			'post_status'		=> 'publish',
			'paged'				=> $_POST['page_number'],
			'orderby'			=> $orderBy,
			'order'				=> $order,
			'meta_query' => array(
												array(
														'key' => 'symbol',
														'value' => $symbol,

												)
										)
		);


		if(isset($category) && !empty($category)){
			$Qargs['cat'] = $category;
		}
		if(isset($post_type) && !empty($post_type)){
			$Qargs['post_type'] = array_map('trim', explode(',', $post_type));
		}
		$template = 'related-post.php';

		$theme_file = locate_template( array( 'related-post/' . $template ) ) ;
		if(isset($theme_file) && !empty($theme_file)){
			$templateFile = $theme_file;
		}else{
			$templateFile = $this->widget_path.'templates/'.$template;
		}
		$result = array();

		$the_query = new WP_Query( $Qargs );
		if ( $the_query->have_posts() ) {
			$result['status'] = 'success';
			ob_start();
			while ( $the_query->have_posts() ) {
				$the_query->the_post();

				$content = get_the_excerpt();
				$content = $this->word_limit_content($content , $content_limit);

				include($templateFile);
			}

			$dataHtml = ob_get_clean();

			$result['response'] = $dataHtml;


		}else{
			$result['status'] = 'failed';
		}
		echo json_encode($result);

		die;

	}
	public function add_scripts(){
		wp_enqueue_script('recent-slider' , $this->widget_url.'assets/scripts.js' ,array('jquery') , '1.0' , true );
		wp_enqueue_style('recent-slider' , $this->widget_url.'assets/style.css' , array(), '1.0' , false );

	}

	function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['number_of_posts'] = ( ! empty( $new_instance['number_of_posts'] ) ) ? strip_tags( $new_instance['number_of_posts'] ) : '';
		$instance['order_by'] = ( ! empty( $new_instance['order_by'] ) ) ? strip_tags( $new_instance['order_by'] ) : '';
		$instance['order'] = ( ! empty( $new_instance['order'] ) ) ? strip_tags( $new_instance['order'] ) : '';
		$instance['is_pagination'] = ( ! empty( $new_instance['is_pagination'] ) ) ? strip_tags( $new_instance['is_pagination'] ) : '';
		$instance['is_thumbail'] = ( ! empty( $new_instance['is_thumbail'] ) ) ? strip_tags( $new_instance['is_thumbail'] ) : '';
		$instance['is_date'] = ( ! empty( $new_instance['is_date'] ) ) ? strip_tags( $new_instance['is_date'] ) : '';
		$instance['is_author'] = ( ! empty( $new_instance['is_author'] ) ) ? strip_tags( $new_instance['is_author'] ) : '';
		$instance['category'] = ( ! empty( $new_instance['category'] ) ) ? strip_tags( $new_instance['category'] ) : '';
		$instance['symbol'] = ( ! empty( $new_instance['symbol'] ) ) ? strip_tags( $new_instance['symbol'] ) : '';
		$instance['post_type'] = ( ! empty( $new_instance['post_type'] ) ) ? strip_tags( $new_instance['post_type'] ) : '';
		$instance['thumb_width'] = ( ! empty( $new_instance['thumb_width'] ) ) ? strip_tags( $new_instance['thumb_width'] ) : '';
		$instance['thumb_height'] = ( ! empty( $new_instance['thumb_height'] ) ) ? strip_tags( $new_instance['thumb_height'] ) : '';
		$instance['content_limit'] = ( ! empty( $new_instance['content_limit'] ) ) ? strip_tags( $new_instance['content_limit'] ) : '';
		$instance['is_excerpt'] = ( ! empty( $new_instance['is_excerpt'] ) ) ? strip_tags( $new_instance['is_excerpt'] ) : '';
		$instance['over_lay'] = ( ! empty( $new_instance['over_lay'] ) ) ? strip_tags( $new_instance['over_lay'] ) : '';
		$instance['class_suffix'] = ( ! empty( $new_instance['class_suffix'] ) ) ? strip_tags( $new_instance['class_suffix'] ) : '';

		return $instance;
	}

	function form( $instance ) {
		// Output admin widget options form
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Latest Post', 'recent-post' );
		$post_type = ! empty( $instance['post_type'] ) ? $instance['post_type'] : '';
		$category = ! empty( $instance['category'] ) ? $instance['category'] : '';
		$symbol = ! empty( $instance['symbol'] ) ? $instance['symbol'] : '';
		$number_of_posts = ! empty( $instance['number_of_posts'] ) ? $instance['number_of_posts'] :5;
		$order_by = ! empty( $instance['order_by'] ) ? $instance['order_by'] : 'ID';
		$order = ! empty( $instance['order'] ) ? $instance['order'] : 'DESC';
		$is_thumbail = ! empty( $instance['is_thumbail'] ) ? $instance['is_thumbail'] : 'yes';
		$is_date = ! empty( $instance['is_date'] ) ? $instance['is_date'] : 'yes';
		$is_author = ! empty( $instance['is_author'] ) ? $instance['is_author'] : 'yes';
		$is_pagination = ! empty( $instance['is_pagination'] ) ? $instance['is_pagination'] : 'yes';
		$thumb_height = ! empty( $instance['thumb_height'] ) ? $instance['thumb_height'] : 75;
		$thumb_width = ! empty( $instance['thumb_width'] ) ? $instance['thumb_width'] : 100;
		$content_limit = ! empty( $instance['content_limit'] ) ? $instance['content_limit'] : 10;
		$is_excerpt = ! empty( $instance['is_excerpt'] ) ? $instance['is_excerpt'] : 'yes';
		$over_lay = ! empty( $instance['over_lay'] ) ? $instance['over_lay'] : '';
		$class_suffix = ! empty( $instance['class_suffix'] ) ? $instance['class_suffix'] : '';
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>"><?php esc_attr_e( 'Post Type:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_type' ) ); ?>" type="text" value="<?php echo esc_attr( $post_type ); ?>">
		<small><?php esc_attr_e( 'Use comma for multiple post types', 'recent-post' ); ?></small>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_attr_e( 'Category ID:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>" type="text" value="<?php echo esc_attr( $category ); ?>">
		<small><?php esc_attr_e( 'Use comma for multiple categories', 'recent-post' ); ?></small>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'number_of_posts' ) ); ?>"><?php esc_attr_e( 'Number Of Post:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number_of_posts' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number_of_posts' ) ); ?>" type="text" value="<?php echo esc_attr( $number_of_posts ); ?>">

		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>"><?php esc_attr_e( 'Order By:', 'recent-post' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order_by' ) ); ?>">
				<option value=""><?php esc_attr_e( 'Select Option', 'recent-post' ); ?></option>
				<option value="ID" <?php echo ($order_by == 'ID')?'selected':''; ?>>ID</option>
				<option value="title" <?php echo ($order_by == 'title')?'selected':''; ?>>Title</option>
				<option value="date" <?php echo ($order_by == 'date')?'selected':''; ?>>Date</option>
				<option value="rand" <?php echo ($order_by == 'rand')?'selected':''; ?>>Random</option>
				<option value="comment_count" <?php echo ($order_by == 'comment_count')?'selected':''; ?>>Comment Count</option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_attr_e( 'Order:', 'recent-post' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>">
				<option value=""><?php esc_attr_e( 'Select Option', 'recent-post' ); ?></option>
				<option value="ASC" <?php echo ($order == 'ASC')?'selected':''; ?>>ASC</option>
				<option value="DESC" <?php echo ($order == 'DESC')?'selected':''; ?>>DESC</option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'is_thumbail' ) ); ?>"><?php esc_attr_e( 'Show Thumbnail:', 'recent-post' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'is_thumbail' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'is_thumbail' ) ); ?>">
				<option value=""><?php esc_attr_e( 'Select Option', 'recent-post' ); ?></option>
				<option value="yes" <?php echo ($is_thumbail == 'yes')?'selected':''; ?>>Yes</option>
				<option value="no" <?php echo ($is_thumbail == 'no')?'selected':''; ?>>No</option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'is_date' ) ); ?>"><?php esc_attr_e( 'Show Date:', 'recent-post' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'is_date' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'is_date' ) ); ?>">
				<option value=""><?php esc_attr_e( 'Select Option', 'recent-post' ); ?></option>
				<option value="yes" <?php echo ($is_date == 'yes')?'selected':''; ?>>Yes</option>
				<option value="no" <?php echo ($is_date == 'no')?'selected':''; ?>>No</option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'is_author' ) ); ?>"><?php esc_attr_e( 'Show Author', 'recent-post' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'is_author' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'is_author' ) ); ?>">
				<option value=""><?php esc_attr_e( 'Select Option', 'recent-post' ); ?></option>
				<option value="yes" <?php echo ($is_author == 'yes')?'selected':''; ?>>Yes</option>
				<option value="no" <?php echo ($is_author == 'no')?'selected':''; ?>>No</option>
			</select>
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'is_pagination' ) ); ?>"><?php esc_attr_e( 'Show Pagination:', 'recent-post' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'is_pagination' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'is_pagination' ) ); ?>">
				<option value=""><?php esc_attr_e( 'Select Option', 'recent-post' ); ?></option>
				<option value="yes" <?php echo ($is_pagination == 'yes')?'selected':''; ?>>Yes</option>
				<option value="no" <?php echo ($is_pagination == 'no')?'selected':''; ?>>No</option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'is_excerpt' ) ); ?>"><?php esc_attr_e( 'Show Excerpt:', 'recent-post' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'is_excerpt' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'is_excerpt' ) ); ?>">
				<option value=""><?php esc_attr_e( 'Select Option', 'recent-post' ); ?></option>
				<option value="yes" <?php echo ($is_excerpt == 'yes')?'selected':''; ?>>Yes</option>
				<option value="no" <?php echo ($is_excerpt == 'no')?'selected':''; ?>>No</option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'content_limit' ) ); ?>"><?php esc_attr_e( 'Content Limit:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'content_limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'content_limit' ) ); ?>" type="text" value="<?php echo esc_attr( $content_limit ); ?>">

		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumb_width' ) ); ?>"><?php esc_attr_e( 'Thumbnail Width:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'thumb_width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumb_width' ) ); ?>" type="text" value="<?php echo esc_attr( $thumb_width ); ?>">
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumb_height' ) ); ?>"><?php esc_attr_e( 'Thumb Height:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'thumb_height' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumb_height' ) ); ?>" type="text" value="<?php echo esc_attr( $thumb_height ); ?>">
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'over_lay' ) ); ?>"><?php esc_attr_e( 'Over Lay RGBA(rgbar(255,255,255,0.8)):', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'over_lay' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'over_lay' ) ); ?>" type="text" value="<?php echo esc_attr( $over_lay ); ?>">
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'class_suffix' ) ); ?>"><?php esc_attr_e( 'Wrapper Class:', 'recent-post' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'class_suffix' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'class_suffix' ) ); ?>" type="text" value="<?php echo esc_attr( $class_suffix ); ?>">
		</p>
		<?php
	}


}

function related_post_with_pagination() {
	register_widget( 'wpRelatedPosts' );
}

add_action( 'widgets_init', 'related_post_with_pagination' );
