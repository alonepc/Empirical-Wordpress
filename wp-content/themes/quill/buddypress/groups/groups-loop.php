<?php

/**
 * BuddyPress - Groups Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter()
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php do_action( 'bp_before_groups_loop' ); ?>

<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) : ?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="group-dir-count-top">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-top">

			<?php bp_groups_pagination_links(); ?>

		</div>

	</div>

	<?php do_action( 'bp_before_directory_groups_list' ); ?>

	<!--<ul id="groups-list" class="item-list" role="main">-->

	<?php while ( bp_groups() ) : bp_the_group(); ?>
	<div class="empirical_group_card">
		<div class="group_icon">
			<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( 'type=full&width=70&height=70' ); ?></a>
		</div>
		<div class="description">
			<h2><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></h2>
			<a href="<?php bp_group_permalink(); ?>"><?php bp_group_description_excerpt(); ?></a>
		</div>
		<div class="statistics">
			<div class="count_icon member_count_icon">
				<i class="fa fa-user"></i>
				<div class="member_count">
					<?php bp_group_member_count(); ?>
				</div>
			</div>
			<div class="count_icon updates_count_icon">
				<i class="fa fa-refresh"></i>
				<div class="update_count">
					<?php
						global $wpdb;
						$group_id = bp_get_group_id();
						$update_count = $wpdb->get_var( "SELECT COUNT(*) FROM wp_bp_activity WHERE item_id=$group_id" );
						echo($update_count);
					?>
				</div>
			</div>
		</div>
	</div>
	<?php if (false) { ?>
		<li <?php bp_group_class(); ?>>
			<div class="item-avatar">
				<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( 'type=thumb&width=72&height=72' ); ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></div>
				<div class="item-meta"><span class="activity"><?php printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() ); ?></span></div>

				<div class="item-desc"><?php bp_group_description_excerpt(); ?></div>

				<?php do_action( 'bp_directory_groups_item' ); ?>

			</div>

			<div class="action">

				<?php do_action( 'bp_directory_groups_actions' ); ?>

				<div class="meta">

					<?php bp_group_type(); ?> / <?php bp_group_member_count(); ?>

				</div>

			</div>

			<div class="clear"></div>
		</li>
	<?php } ?>
	
	<?php endwhile; ?>

	<!--</ul>-->

	<?php do_action( 'bp_after_directory_groups_list' ); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="group-dir-count-bottom">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-bottom">

			<?php bp_groups_pagination_links(); ?>

		</div>

	</div>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no groups found.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_groups_loop' ); ?>
