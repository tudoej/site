<?php appthemes_before_comments(); ?>

<?php if ( have_comments() ) { ?>
	<div class="commentlist">
		<?php va_event_list_comments(); ?>
	</div>

	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) { // are there comments to navigate through? ?>
	<nav id="comment-nav-below">
		<h4 class="assistive-text"><?php _e( 'Comment navigation', APP_TD ); ?></h4>
		<div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', APP_TD ) ); ?></div>
		<div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', APP_TD ) ); ?></div>
	</nav>
	<?php } ?>

<?php } elseif ( ! comments_open() ) { ?>
	<p class="nocomments"><?php _e( 'Comments are closed.', APP_TD ); ?></p>
<?php } ?>

<?php appthemes_after_comments(); ?>