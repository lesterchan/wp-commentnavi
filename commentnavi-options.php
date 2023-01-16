<?php
### Variables Variables Variables
$base_name = plugin_basename('wp-commentnavi/commentnavi-options.php');
$base_page = 'admin.php?page='.$base_name;

### Form Processing 
if(!empty($_POST['Submit'])) {
    check_admin_referer( 'wp-commentnavi_options' );
    $commentnavi_options = array();
    $commentnavi_options['pages_text']      = ! empty( $_POST['commentnavi_pages_text'] )   ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_pages_text'] ) ) ) : '';
    $commentnavi_options['current_text']    = ! empty( $_POST['commentnavi_current_text'] ) ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_current_text'] ) ) ) : '';
    $commentnavi_options['page_text']       = ! empty( $_POST['commentnavi_page_text'] )    ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_page_text'] ) ) ) : '';
    $commentnavi_options['first_text']      = ! empty( $_POST['commentnavi_first_text'] )   ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_first_text'] ) ) ) : '';
    $commentnavi_options['last_text']       = ! empty( $_POST['commentnavi_last_text'] )    ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_last_text'] ) ) ) : '';
    $commentnavi_options['next_text']       = ! empty( $_POST['commentnavi_next_text'] )    ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_next_text'] ) ) ) : '';
    $commentnavi_options['prev_text']       = ! empty( $_POST['commentnavi_prev_text'] )    ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_prev_text'] ) ) ) : '';
    $commentnavi_options['dotright_text']   = ! empty( $_POST['commentnavi_dotright_text'] )? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_dotright_text'] ) ) ) : '';
    $commentnavi_options['dotleft_text']    = ! empty( $_POST['commentnavi_dotleft_text'] ) ? addslashes( trim( wp_filter_post_kses( $_POST['commentnavi_dotleft_text'] ) ) ) : '';
    $commentnavi_options['style']           = ! empty( $_POST['commentnavi_style'] )        ? intval( trim( $_POST['commentnavi_style'] ) ) : 1;
    $commentnavi_options['num_pages']       = ! empty( $_POST['commentnavi_num_pages'] )    ? intval( trim( $_POST['commentnavi_num_pages'] ) ) : 5;
    $commentnavi_options['always_show']     = ! empty( $_POST['commentnavi_always_show'])   ? intval( trim( $_POST['commentnavi_always_show'] ) ) : 0;
    $update_commentnavi_queries = array();
    $update_commentnavi_text = array();
    $update_commentnavi_queries[] = update_option('commentnavi_options', $commentnavi_options);
    $update_commentnavi_text[] = __('Comment Navigation Options', 'wp-commentnavi');
    $i=0;
    $text = '';
    foreach($update_commentnavi_queries as $update_commentnavi_query) {
        if($update_commentnavi_query) {
            $text .= '<p style="color: green;">'.$update_commentnavi_text[$i].' '.__('Updated', 'wp-commentnavi').'</p>';
        }
        $i++;
    }
    if(empty($text)) {
        $text = '<p style="color: red;">'.__('No Comment Navigation Option Updated', 'wp-commentnavi').'</p>';
    }
}

$commentnavi_options = get_option('commentnavi_options');
?>
<?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
<form method="post" action="<?php echo admin_url('admin.php?page='.plugin_basename(__FILE__)); ?>">
    <?php wp_nonce_field( 'wp-commentnavi_options' ); ?>
    <div class="wrap">
        <h2><?php _e('Comment Navigation Options', 'wp-commentnavi'); ?></h2>
        <h3><?php _e('Comment Navigation Text', 'wp-commentnavi'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Number Of Pages', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_pages_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['pages_text'] ) ); ?>" size="50" /><br />
                    %CURRENT_PAGE% - <?php _e('The current page number.', 'wp-commentnavi'); ?><br />
                    %TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-commentnavi'); ?>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Current Page', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_current_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['current_text'] ) ); ?>" size="30" /><br />
                    %PAGE_NUMBER% - <?php _e('The page number.', 'wp-commentnavi'); ?><br />
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Page', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_page_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['page_text'] ) ); ?>" size="30" /><br />
                    %PAGE_NUMBER% - <?php _e('The page number.', 'wp-commentnavi'); ?><br />
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For First Comment', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_first_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['first_text'] ) ); ?>" size="30" /><br />
                    %TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-commentnavi'); ?>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Last Comment', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_last_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['last_text'] ) ); ?>" size="30" /><br />
                    %TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-commentnavi'); ?>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Next Comment', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_next_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['next_text'] ) ); ?>" size="30" />
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Previous Comment', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_prev_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['prev_text'] ) ); ?>" size="30" />
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Next ...', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_dotright_text" value="<?php echo stripslashes( esc_attr( $commentnavi_options['dotright_text'] ) ); ?>" size="30" />
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php _e('Text For Previous ...', 'wp-commentnavi'); ?></th>
                <td>
                    <input type="text" name="commentnavi_dotleft_text" value="<?php echo stripslashes( esc_attr($commentnavi_options['dotright_text'] ) ); ?>" size="30" />
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
                    <input type="text" name="commentnavi_num_pages" value="<?php echo stripslashes( esc_attr( $commentnavi_options['num_pages'] ) ); ?>" size="4" />
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