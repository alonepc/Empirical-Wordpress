<?php do_action( 'bp_before_directory_members_page' ); ?>

<div id="buddypress">
	
	<div id="buddypress-sidebar">
		<ul class="buddypress-group-list">
			<li class="active">
				<a href="#" data-filter="" data-title="Quill Community" data-description="" data-image="/wp-content/themes/quill/assets/img/avatar.jpg">
					<i class="fa fa-university"></i>
					Quill Community
				</a>
			</li>
			
		<?php
			if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) :
				while ( bp_groups() ) : bp_the_group();
				
				
			$avatar = bp_core_fetch_avatar( array(
	          'item_id'    => bp_get_group_id(),
	          'title'      => bp_get_group_name(),
	          'avatar_dir' => 'group-avatars',
	          'object'     => 'group',
	          'type'       => 'full',
	          'width'      => 50,
	          'height'     => 50
			) );
			
			$avatarDoc = new DOMDocument();
			$avatarDoc->loadHTML($avatar);
			$xpath = new DOMXPath($avatarDoc);
			$avatarSrc = $xpath->evaluate("string(//img/@src)");
			
		?>
		
		<li>
			<a href="#<?php bp_group_slug(); ?>" data-filter=".<?php bp_group_slug(); ?>" data-title="<?php bp_group_name(); ?>" data-description="<?php bp_group_description(); ?>" data-image="<?php echo($avatarSrc); ?>">
				<?php bp_group_avatar( 'type=full&width=30&height=30' ); ?>
				<?php bp_group_name(); ?>
			</a>
		</li>
				
		<?php	
			endwhile; endif;
		?>
			
		</ul>
	</div>
	
	<?php do_action( 'bp_before_directory_members' ); ?>

	<?php do_action( 'bp_before_directory_members_content' ); ?>


	<?php do_action( 'bp_before_directory_members_tabs' ); ?>

	<form action="" method="post" id="members-directory-form" class="dir-form item-body">
		
		<div class="banner-header">
			<a href="#" class="banner-link">
				<div class="left">
					<img src="/wp-content/themes/quill/assets/img/avatar.jpg" />
				</div>
				<div class="right">
					<h2>Quill Community</h2>
					<div class="text">Quill is an open source literacy tool that provides writing, grammar, and vocabulary activities to students. Quill is being built by a community of educators and developers.</div>
				</div>
			</a>
		</div>
		
		<div id="members-dir-list" class="members dir-list">
			<?php bp_get_template_part( 'members/members-loop' ); ?>
		</div><!-- #members-dir-list -->

		<?php do_action( 'bp_directory_members_content' ); ?>

		<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ); ?>

		<?php do_action( 'bp_after_directory_members_content' ); ?>

	</form><!-- #members-directory-form -->

	<?php do_action( 'bp_after_directory_members' ); ?>

</div><!-- #buddypress -->

<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.isotope/2.0.0/isotope.pkgd.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/3.0.4/jquery.imagesloaded.min.js"></script>

<script type="text/javascript">
	jQuery(document).ready(function($){
		var $container = $('ul#members-list').imagesLoaded( function() {
			// init
			$container.isotope({
			  // options
			  itemSelector: 'li.member',
			  layoutMode: 'fitRows',
			  filter: '*'
			});
		});

		$('ul.buddypress-group-list li a').on( 'click', function() {
			$('ul.buddypress-group-list li').removeClass('active');
			$(this).parent().addClass('active');
			var filterValue = $(this).data('filter');
			var filterAnchor = $(this).attr('href');
			filterAnchor = filterAnchor.slice(1);
			filterAnchor = '/teams/' + filterAnchor;

			$container.isotope({ filter: filterValue });
			
			var filterTitle = $(this).data('title');
			var filterDescription = $(this).data('description');
			var filterImage = $(this).data('image');
			$('.banner-header h2').text(filterTitle);
			$('.banner-header .text').html(filterDescription);
			$('.banner-header img').attr('src', filterImage);
			$('.banner-header a.banner-link').attr('href', filterAnchor);

			
		});
		
	});
</script>
<?php do_action( 'bp_after_directory_members_page' ); ?>