<?php
/*
+----------------------------------------------------------------+
|																							|
|	WordPress 2.8 Plugin: WP-CommentNavi 1.10							|
|	Copyright (c) 2009 Lester "GaMerZ" Chan									|
|																							|
|	File Written By:																	|
|	- Lester "GaMerZ" Chan															|
|	- http://lesterchan.net															|
|																							|
|	File Information:																	|
|	- Comment Navigation Options Page											|
|	- wp-content/plugins/wp-commentnavi/commentnavi-options.php	|
|																							|
+----------------------------------------------------------------+
*/


### Variables Variables Variables
$base_name = plugin_basename('wp-commentnavi/commentnavi-options.php');
$base_page = 'admin.php?page='.$base_name;
$mode = isset($_GET['mode']) ? trim($_GET['mode']) : '';
$commentnavi_settings = array('commentnavi_options');


### Form Processing 
// Update Options
if(!empty($_POST['Submit'])) {
	$commentnavi_options = array();
	$commentnavi_options['pages_text'] = addslashes($_POST['commentnavi_pages_text']);
	$commentnavi_options['current_text'] = addslashes($_POST['commentnavi_current_text']);
	$commentnavi_options['page_text'] = addslashes($_POST['commentnavi_page_text']);
	$commentnavi_options['first_text'] = addslashes($_POST['commentnavi_first_text']);
	$commentnavi_options['last_text'] = addslashes($_POST['commentnavi_last_text']);
	$commentnavi_options['next_text'] = addslashes($_POST['commentnavi_next_text']);
	$commentnavi_options['prev_text'] = addslashes($_POST['commentnavi_prev_text']);
	$commentnavi_options['dotright_text'] = addslashes($_POST['commentnavi_dotright_text']);
	$commentnavi_options['dotleft_text'] = addslashes($_POST['commentnavi_dotleft_text']);
	$commentnavi_options['style'] = intval(trim($_POST['commentnavi_style']));
	$commentnavi_options['num_pages'] = intval(trim($_POST['commentnavi_num_pages']));
	$commentnavi_options['always_show'] = intval(trim($_POST['commentnavi_always_show']));
	$update_commentnavi_queries = array();
	$update_commentnavi_text = array();
	$update_commentnavi_queries[] = update_option('commentnavi_options', $commentnavi_options);
	$update_commentnavi_text[] = __('Comment Navigation Options', 'wp-commentnavi');
	$i=0;
	$text = '';
	foreach($update_commentnavi_queries as $update_commentnavi_query) {
		if($update_commentnavi_query) {
			$text .= '<font color="green">'.$update_commentnavi_text[$i].' '.__('Updated', 'wp-commentnavi').'</font><br />';
		}
		$i++;
	}
	if(empty($text)) {
		$text = '<font color="red">'.__('No Comment Navigation Option Updated', 'wp-commentnavi').'</font>';
	}
}
// Uninstall WP-CommentNavi
if(!empty($_POST['do'])) {
	switch($_POST['do']) {		
		case __('UNINSTALL WP-CommentNavi', 'wp-commentnavi') :
			if(trim($_POST['uninstall_commentnavi_yes']) == 'yes') {
				echo '<div id="message" class="updated fade">';
				echo '<p>';
				foreach($commentnavi_settings as $setting) {
					$delete_setting = delete_option($setting);
					if($delete_setting) {
						echo '<font color="green">';
						printf(__('Setting Key \'%s\' has been deleted.', 'wp-commentnavi'), "<strong><em>{$setting}</em></strong>");
						echo '</font><br />';
					} else {
						echo '<font color="red">';
						printf(__('Error deleting Setting Key \'%s\'.', 'wp-commentnavi'), "<strong><em>{$setting}</em></strong>");
						echo '</font><br />';
					}
				}
				echo '</p>';
				echo '</div>'; 
				$mode = 'end-UNINSTALL';
			}
			break;
	}
}


### Determines Which Mode It Is
switch($mode) {
		//  Deactivating WP-CommentNavi
		case 'end-UNINSTALL':
			$deactivate_url = 'plugins.php?action=deactivate&amp;plugin=wp-commentnavi/wp-commentnavi.php';
			if(function_exists('wp_nonce_url')) { 
				$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-plugin_wp-commentnavi/wp-commentnavi.php');
			}
			echo '<div class="wrap">';
			echo '<h2>'.__('Uninstall WP-CommentNavi', 'wp-commentnavi').'</h2>';
			echo '<p><strong>'.sprintf(__('<a href="%s">Click Here</a> To Finish The Uninstallation And WP-CommentNavi Will Be Deactivated Automatically.', 'wp-commentnavi'), $deactivate_url).'</strong></p>';
			echo '</div>';
			break;
	// Main Page
	default:
		$commentnavi_options = get_option('commentnavi_options');
?>
<?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
<form method="post" action="<?php echo admin_url('admin.php?page='.plugin_basename(__FILE__)); ?>">
<div class="wrap"> 
	<?php screen_icon(); ?>
	<h2><?php _e('Comment Navigation Options', 'wp-commentnavi'); ?></h2>
	<h3><?php _e('Comment Navigation Text', 'wp-commentnavi'); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Number Of Pages', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_pages_text" value="<?php echo stripslashes($commentnavi_options['pages_text']); ?>" size="50" /><br />
				%CURRENT_PAGE% - <?php _e('The current page number.', 'wp-commentnavi'); ?><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-commentnavi'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Current Page', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_current_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['current_text'])); ?>" size="30" /><br />
				%PAGE_NUMBER% - <?php _e('The page number.', 'wp-commentnavi'); ?><br />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Page', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_page_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['page_text'])); ?>" size="30" /><br />
				%PAGE_NUMBER% - <?php _e('The page number.', 'wp-commentnavi'); ?><br />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For First Comment', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_first_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['first_text'])); ?>" size="30" /><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-commentnavi'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Last Comment', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_last_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['last_text'])); ?>" size="30" /><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-commentnavi'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Next Comment', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_next_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['next_text'])); ?>" size="30" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Previous Comment', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_prev_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['prev_text'])); ?>" size="30" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Next ...', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_dotright_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['dotright_text'])); ?>" size="30" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Previous ...', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_dotleft_text" value="<?php echo stripslashes(htmlspecialchars($commentnavi_options['dotright_text'])); ?>" size="30" />
			</td>
		</tr>
	</table>
	<h3><?php _e('Comment Navigation Options', 'wp-commentnavi'); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><?php _e('Comment Navigation Style', 'wp-commentnavi'); ?></th>
			<td>
				<select name="commentnavi_style" size="1">
					<option value="1"<?php selected('1', $commentnavi_options['style']); ?>><?php _e('Normal', 'wp-commentnavi'); ?></option>
					<option value="2"<?php selected('2', $commentnavi_options['style']); ?>><?php _e('Drop Down List', 'wp-commentnavi'); ?></option>
				</select>
			</td> 
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Number Of Pages To Show?', 'wp-commentnavi'); ?></th>
			<td>
				<input type="text" name="commentnavi_num_pages" value="<?php echo stripslashes($commentnavi_options['num_pages']); ?>" size="4" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Always Show Comment Navigation?', 'wp-commentnavi'); ?></th>
			<td>
				<select name="commentnavi_always_show" size="1">
					<option value="1"<?php selected('1', $commentnavi_options['always_show']); ?>><?php _e('Yes', 'wp-commentnavi'); ?></option>
					<option value="0"<?php selected('0', $commentnavi_options['always_show']); ?>><?php _e('No', 'wp-commentnavi'); ?></option>
				</select>
			</td> 
		</tr>
	</table>
	<p class="submit">
		<input type="submit" name="Submit" class="button" value="<?php _e('Save Changes', 'wp-commentnavi'); ?>" />
	</p>
</div>
</form> 
<p>&nbsp;</p>

<!-- Uninstall WP-CommentNavi -->
<form method="post" action="<?php echo admin_url('admin.php?page='.plugin_basename(__FILE__)); ?>">
<div class="wrap"> 
	<h3><?php _e('Uninstall WP-CommentNavi', 'wp-commentnavi'); ?></h3>
	<p>
		<?php _e('Deactivating WP-CommentNavi plugin does not remove any data that may have been created, such as the comment navigation options. To completely remove this plugin, you can uninstall it here.', 'wp-commentnavi'); ?>
	</p>
	<p style="color: red">
		<strong><?php _e('WARNING:', 'wp-commentnavi'); ?></strong><br />
		<?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to back up all the data first.', 'wp-commentnavi'); ?>
	</p>
	<p style="color: red">
		<strong><?php _e('The following WordPress Options will be DELETED:', 'wp-commentnavi'); ?></strong><br />
	</p>
	<table class="widefat">
		<thead>
			<tr>
				<th><?php _e('WordPress Options', 'wp-commentnavi'); ?></th>
			</tr>
		</thead>
		<tr>
			<td valign="top">
				<ol>
				<?php
					foreach($commentnavi_settings as $settings) {
						echo '<li>'.$settings.'</li>'."\n";
					}
				?>
				</ol>
			</td>
		</tr>
	</table>
	<p>&nbsp;</p>
	<p style="text-align: center;">
		<input type="checkbox" name="uninstall_commentnavi_yes" value="yes" />&nbsp;<?php _e('Yes', 'wp-commentnavi'); ?><br /><br />
		<input type="submit" name="do" value="<?php _e('UNINSTALL WP-CommentNavi', 'wp-commentnavi'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Uninstall WP-CommentNavi From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.', 'wp-commentnavi'); ?>')" />
	</p>
</div> 
</form>
<?php
} // End switch($mode)
?>
