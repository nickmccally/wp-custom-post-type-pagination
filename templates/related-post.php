<li class="lp-post latest_posts-wgt-post">
		<?php
			$postLink = get_permalink();
		if(isset($is_thumbail) && !empty($is_thumbail) && $is_thumbail == 'yes'):?>
	<a href="<?php echo esc_url($postLink , 'recent-post'); ?>" class="latest_posts-wgt-thumb">
		<?php
			$postID = get_the_ID();

			$getFeaturedImage = get_the_post_thumbnail_url( $postID);

			if(!empty($getFeaturedImage)){
				$getFeaturedImageURL = mq_resize($getFeaturedImage, $thumb_width, $thumb_height, true);
				$imageSize = getimagesize($getFeaturedImageURL);

		?>
			<img src="<?php echo esc_url($getFeaturedImageURL , 'recent-post'); ?>" <?php echo (isset($imageSize[3]) && !empty($imageSize[3]))?$imageSize[3]:'' ?> class="attachment-<?php echo (isset($imageSize[0]) && !empty($imageSize[0]))?$imageSize[0]:''?>x<?php echo (isset($imageSize[1]) && !empty($imageSize[1]))?$imageSize[1]:'';?> wp-post-image" alt="" >
		<?php  } ?>
	</a>
	<?php endif; ?>


	<h4 class="title latest_posts-wgt-title mb-0 pb-0 mt-3 h5" itemprop="headline">
		<a href="<?php echo esc_url($postLink , 'recent-post'); ?>" class="latest_posts-wgt-title-link" title="Progressively repurpose cutting-edge models">
			<?php the_title(); ?>
		</a>
	</h4>

	<?php if(isset($is_excerpt) && $is_excerpt == 'yes'):?>
		<p><?php echo esc_html($content , 'recent-post'); ?>...</p>
	<?php endif; ?>
	<?php if((isset($is_date) && $is_date == 'yes') || (isset($is_author) && $is_author == 'yes')):?>

		<?php if(isset($is_date) && $is_date == 'yes'):?>
			<span><?php the_date('d M, Y'); ?></span>
		<?php endif; ?>
		<?php if(isset($is_author) && $is_author == 'yes'):
				$authorUrl = get_author_posts_url(get_the_author_meta( 'ID' ));
		?>
			By <span><a href="<?php echo  esc_url($authorUrl, 'recent-post'); ?>"><?php the_author_link(); ?></a></span>
		<?php endif; ?>
	<?php endif; ?>

</li>
