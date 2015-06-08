<?php
require_once("gdfl_lists.php");

add_action( 'admin_menu', 'gdfl_plugin_menu' );

function gdfl_plugin_menu() {
	add_plugins_page( 'GDFL options', "Google drive folder list options", 'manage_options', 'gdfl_main', 'gdfl_plugin_options');
}

function update_options($name, $value){
	if(get_option('gdfl_'.$name,false) === false){
		add_option('gdfl_'.$name,$value);
	} else {
		update_option('gdfl_'.$name,$value);
	}
}

function gdfl_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$gdfl_cssStyles = getGdflStylesList();
	
	if(count($_POST) > 0){
		if(isset($_POST['show_preview_default'])) { update_options('show_preview_default',1); } else { update_options('show_preview_default',0); }
		
		if(isset($_POST['subfolders_maxdepth'])) { update_options('subfolders_maxdepth',$_POST['subfolders_maxdepth']); } else { update_options('subfolders_maxdepth',1); }
		
		foreach($gdfl_cssStyles as $style){
			if(isset($_POST['css_'.$style])) update_options('css_'.$style,$_POST['css_'.$style]);
		}
	}
	
?>
<h2>Google drive folder list</h2>
<form method="post" action="">
<h3>Common settings</h3>
<table class="form-table">
<tbody>
	<tr valign="top">
		<th scope="row">
			Show preview by default?
		</th>
		<td> 
			<fieldset>
				<legend class="screen-reader-text"><span>Show preview by default?</span></legend>
				<label for="show_preview_default">
					<input name="show_preview_default" type="checkbox" <?php echo get_option('gdfl_show_preview_default',0) == 1 ? 'checked="checked"' : ''; ?> id="show_preview_default" value="1">
				</label>
			</fieldset>
		</td>
	</tr>
	</tbody>
</table>
<h3>Subfolders settings</h3>
<table class="form-table">
	<tbody>
	<tr valign="top">
		<th scope="row">
			Maximum depth
		</th>
		<td>
			<legend class="screen-reader-text"><span>Maximum depth</span></legend>
				<label for="subfolders_maxdepth">
					<input name="subfolders_maxdepth" type="text" id="subfolders_maxdepth" value="<?php echo get_option('gdfl_subfolders_maxdepth',''); ?>" />
				</label>
			</fieldset>
		</td>
	</tr>
</tbody>
</table>
<h3>Styling settings</h3>
<table class="form-table">
	<tbody>
	<tr valign="top">
		<td>
			<?php foreach($gdfl_cssStyles as $style){ ?>
			<p>
				<span class="description">.<?php echo $style; ?></span>
				<input name="css_<?php echo $style; ?>" type="text" id="css_<?php echo $style; ?>" value="<?php echo get_option('gdfl_css_'.$style,''); ?>" class="regular-text code" style="width:100%;"/></p>
			<?php } ?>
		</td>
	</tr>
</tbody>
</table>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
</form>
<?php
}
?>