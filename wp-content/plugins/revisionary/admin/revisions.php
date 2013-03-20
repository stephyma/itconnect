<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

/**
 * revisions.php
 * 
 * Revisions Manager for Revisionary plugin, derived and heavily expanded from WP 2.8.4 core
 *
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009-2011
 * 
 */
 
include_once( dirname(__FILE__).'/revision-ui_rvy.php' ); 

if ( defined( 'FV_FCK_NAME' ) && current_user_can('activate_plugins') ) {
	echo( '<div class="error">' );
	_e( "<strong>Note:</strong> For visual display of revisions, add the following code to foliopress-wysiwyg.php:<br />&nbsp;&nbsp;if ( strpos( $" . "_SERVER['REQUEST_URI'], 'admin.php?page=rvy-revisions' ) ) return;", 'revisionary');
	echo( '</div><br />' );
}
//wp_reset_vars( array('revision', 'left', 'right', 'action', 'revision_status') );

if ( ! empty($_GET['revision']) )
	$revision_id = absint($_GET['revision']);

if ( ! empty($_GET['left']) )
	$left = absint($_GET['left']);
else
	$left = '';

if ( ! empty($_GET['right']) )
	$right = absint($_GET['right']);
else
	$right = '';

if ( ! empty($_GET['revision_status']) )
	$revision_status = $_GET['revision_status'];
else
	$revision_status = '';
	
if ( ! empty($_GET['action']) )
	$action = $_GET['action'];
else
	$action = '';

if ( ! empty($_GET['restored_post'] ) )
	$revision_id = $_GET['restored_post'];

if ( empty($revision_id) && ! $left && ! $right ) {
	echo( '<div><br />' );
	_e( 'No revision specified.', 'revisionary');
	echo( '</div>' );
	return;
}

$revision_status_captions = array( 'inherit' => __( 'Past', 'revisionary' ), 'pending' => __awp('Pending', 'revisionary'), 'future' => __awp( 'Scheduled', 'revisionary' ) );

if( 'edit' == $action )
	$action = 'view';

switch ( $action ) :
case 'diff' :
	if ( !$left_revision  = get_post( $left ) )
		break;
	if ( !$right_revision = get_post( $right ) )
		break;

	// actual status of compared objects overrides any revision_Status arg passed in
	if ( 'revision' == $left_revision->post_type )
		$revision_status = $left_revision->post_status;
	else
		$revision_status = $right_revision->post_status;
		
	// TODO: review revision read_post implementation with Press Permit
	if ( ( ! current_user_can( 'read_post', $left_revision->ID ) && ! current_user_can( 'edit_post', $left_revision->ID ) ) || ( ! current_user_can( 'read_post', $right_revision->ID ) && ! current_user_can( 'edit_post', $right_revision->ID ) ) )
		break;

	if ( $left_revision->ID == $right_revision->post_parent ) // right is a revision of left
		$rvy_post = $left_revision;
	elseif ( $left_revision->post_parent == $right_revision->ID ) // left is a revision of right
		$rvy_post = $right_revision;
	elseif ( $left_revision->post_parent == $right_revision->post_parent ) // both are revisions of common parent
		$rvy_post = get_post( $left_revision->post_parent );
	else
		break; // Don't diff two unrelated revisions

	if (
		// They're the same
		$left_revision->ID == $right_revision->ID
	||
		// Neither is a revision
		( !wp_get_post_revision( $left_revision->ID ) && !wp_get_post_revision( $right_revision->ID ) )
	)
		break;

	$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";

	$h2 = sprintf( __( '%1$s Revisions for &#8220;%2$s&#8221;', 'revisionary' ), $revision_status_captions[$revision_status], $post_title );

	$left  = $left_revision->ID;
	$right = $right_revision->ID;

	break;
case 'view' :
default :
	$left = 0;
	$right = 0;
	$h2 = '';
	
	if ( ! $revision = wp_get_post_revision( $revision_id ) ) {
		// Support published post/page in revision argument
		if ( ! $rvy_post = get_post( $revision_id) )
			break;

		$public_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
	
		if ( ! in_array( $rvy_post->post_type, $public_types ) ) {
			$rvy_post = '';  // todo: is this necessary?
			break;
		}

		// revision_id is for a published post.  List all its revisions - either for type specified or default to past
		if ( ! $revision_status )
			$revision_status = 'inherit';
			
		if ( !current_user_can( 'read_post', $rvy_post->ID ) )
			break;
			
	} else {
		if ( !$rvy_post = get_post( $revision->post_parent ) )
			break;

		// actual status of compared objects overrides any revision_Status arg passed in
		$revision_status = $revision->post_status;	
		
		// TODO: review revision read_post implementation with Press Permit

		//if ( !current_user_can( 'read_post', $revision->ID ) || !current_user_can( 'read_post', $rvy_post->ID ) ) {  // TODO: review PP has_cap filtering for revisions
		if ( ! current_user_can( 'read_post', $rvy_post->ID ) ) {
			break;
		}
	}

	if ( $type_obj = get_post_type_object( $rvy_post->post_type ) ) {
		$edit_cap = $type_obj->cap->edit_post;
		$edit_others_cap = $type_obj->cap->edit_others_posts;
		$delete_cap = $type_obj->cap->delete_post;
	}

	// Sets up the diff radio buttons
	$right = $rvy_post->ID;

	// temporarily remove filter so we don't change it into a revisions.php link
	global $revisionary;
	remove_filter( 'get_edit_post_link', array($revisionary->admin, 'flt_edit_post_link'), 10, 3 );
		
	if ( $revision ) {
		$left = $revision_id;
		$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";

		$revision_title = wp_post_revision_title( $revision, false );
		
		$caption = ( strpos($revision->post_name, '-autosave' ) ) ? '' : $revision_status_captions[$revision_status];
		
		// TODO: combine this code with captions for front-end preview approval bar
		switch ( $revision_status ) :
		case 'inherit':
			if ( strpos( $revision->post_name, '-autosave' ) )
				$h2 = sprintf( __( 'Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			else
				$h2 = sprintf( __( 'Past Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		case 'pending':
			$h2 = sprintf( __( 'Pending Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		case 'future':
			$h2 = sprintf( __( 'Scheduled Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		endswitch;

		if ( ('diff' != $action) && ($rvy_post->ID != $revision->ID) ) {
			if ( agp_user_can( $edit_cap, $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) ) {
				switch( $revision->post_status ) :
				case 'future' :
					$caption = str_replace( ' ', '&nbsp;', __('Publish Now', 'revisionary') );
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'restore' ) ), "restore-post_$rvy_post->ID|$revision->ID" );
					break;
				case 'pending' :
					if ( strtotime($revision->post_date_gmt) > agp_time_gmt() ) {
						$caption = str_replace( ' ', '&nbsp;', __('Schedule Now', 'revisionary') );
					} else {
						$caption = str_replace( ' ', '&nbsp;', __('Publish Now', 'revisionary') );
					}
					
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'approve' ) ), "approve-post_$rvy_post->ID|$revision->ID" );
					break;
				default :
					$caption = str_replace( ' ', '&nbsp;', __('Restore Now', 'revisionary') );
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'restore' ) ), "restore-post_$rvy_post->ID|$revision->ID" );
				endswitch;
		
				$restore_link = '<a href="' . $link . '">' .$caption . "</a> ";
			} else
				$restore_link = '';
		}
		
	} else {
		$revision = $rvy_post;	

		$link = apply_filters( 'get_edit_post_link', admin_url("{$rvy_post->post_type}.php?action=edit&post=$revision_id"), $revision_id, '' );

		$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";
		
		$revision_title = wp_post_revision_title( $revision, false );
		$h2 = sprintf( __( '&#8220;%1$s&#8221; (Current Revision)' ), $post_title );
	}

	add_filter( 'get_edit_post_link', array($revisionary->admin, 'flt_edit_post_link'), 10, 3 );
	
	// pending revisions are newer than current revision
	if ( 'pending' == $revision_status ) {
		$buffer_left = $left;
		$left  = $right;
		$right = $buffer_left;
	}

	break;
endswitch;


if ( empty($revision) && empty($right_revision) && empty($left_revision) ) {
	echo( '<div><br />' );
	_e( 'The requested revision does not exist.', 'revisionary');
	echo( '</div>' );
	return;
}

if ( ! $revision_status )
	$revision_status = 'inherit'; 	// default to showing past revisions
?>

<div class="wrap">

<form name="post" action="" method="post" id="post">

<?php
global $current_user;

if ( 'diff' != $action ) {
	if ( ! $can_fully_edit_post = agp_user_can( $edit_cap, $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) ) {
		// post-assigned Revisor role is sufficient to edit others' revisions, but post-assigned Contributor role is not
		if ( isset( $GLOBALS['cap_interceptor'] ) )
			$GLOBALS['cap_interceptor']->require_full_object_role = true;
		
		$_can_edit_others = agp_user_can( $edit_others_cap, $rvy_post->ID );

		if ( isset( $GLOBALS['cap_interceptor'] ) )
			$GLOBALS['cap_interceptor']->require_full_object_role = false;
	}

	$can_edit = ( 'revision' == $revision->post_type ) && (
		$can_fully_edit_post || 
		( ( $revision->post_author == $current_user->ID || $_can_edit_others ) && ( 'pending' == $revision->post_status ) ) 
		 );

	if ( $can_edit ) {
		wp_nonce_field('update-revision_' .  $revision->ID);

		echo "<input type='hidden' id='revision_ID' name='revision_ID' value='" . esc_attr($revision->ID) . "' />";
	}
}
?>

<table class="rvy-editor-table">
<tr><td class="rvy-editor-table-top">
<h2><?php 

echo $h2; 
if ( ! empty($restore_link) )
	echo "<span class='rs-revision_top_action rvy-restore-link'> $restore_link</span>";	
?></h2>

<?php
	$msg = '';

	if ( ! empty($_GET['deleted']) )
		$msg = __('The revision was deleted.', 'revisionary');

	elseif ( isset($_GET['bulk_deleted']) )
		$msg = sprintf( _n( '%s revision was deleted', '%s revisions were deleted', $_GET['bulk_deleted'] ), number_format_i18n( $_GET['bulk_deleted'] ) );
		
	elseif ( ! empty($_GET['rvy_updated']) )
		$msg = __('The revision was updated.', 'revisionary');
		
	elseif ( ! empty($_GET['restored_post'] ) )
		$msg = __('The revision was restored.', 'revisionary');
		
	elseif ( ! empty($_GET['scheduled'] ) )
		$msg = __('The revision was scheduled for publication.', 'revisionary');

	elseif ( ! empty($_GET['published_post'] ) )
		$msg = __('The revision was published.', 'revisionary');

	elseif ( ! empty($_GET['delete_request']) ) {
		if ( current_user_can( $delete_cap, $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) 
		|| ( ( 'pending' == $revision->post_status ) && ( $revision->post_author == $current_user->ID ) ) )
			$msg = __('To delete the revision, click the link below.', 'revisionary');
		else
			$msg = __('You do not have permission to delete that revision.', 'revisionary');

	} elseif ( ! empty($_GET['unscheduled'] ) )
		$msg = __('The revision was unscheduled.', 'revisionary');

	
	if ( $msg ) {
		echo '<div id="message" class="updated fade clear rvy-message"><p>';
		echo $msg;
		echo '</p></div><br />';	
	}
?>
</td>
<?php
if ( ( ! $action || ( 'view' == $action ) ) && ( $revision ) ) {
echo '<td class="rvy-date-selection">';
	
	// date stuff
	// translators: Publish box date formt, see http://php.net/date
	$datef = __awp( 'M j, Y @ G:i' );

	if ( in_array( $revision->post_status, array( 'publish', 'private' ) ) )
		$stamp = __('Published on: <strong>%1$s</strong>', 'revisionary');
	elseif ( 'future' == $revision->post_status )
		$stamp = __('Scheduled for: <strong>%1$s</strong>', 'revisionary');
	elseif ( 'pending' == $revision->post_status ) {
		if ( strtotime($revision->post_date_gmt) > agp_time_gmt() )
			$stamp = __('Requested Publish Date: <strong>%1$s</strong>', 'revisionary');
		else
			$stamp = __('Requested Publish Date: <strong>Immediate</strong>', 'revisionary');
	} else
		$stamp = __('Modified on: <strong>%1$s</strong>', 'revisionary');

	$use_date = ( 'inherit' == $revision->post_status ) ? $revision->post_modified : $revision->post_date;
	
	$date = agp_date_i18n( $datef, strtotime( $use_date ) );
	
	echo '<div id="rvy_time" class="curtime clear"><span id="saved_timestamp">';
	printf($stamp, $date);
	echo '</span>';
	
	if ( $can_edit && in_array( $revision->post_status, array( 'pending', 'future' ) ) ) {
		echo '&nbsp;<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex="4">';
		echo __awp('Edit');
		echo '</a>';
	}
	
	echo '<div id="selected_timestamp_div" style="display:none;">';
	echo '<span id="selected_timestamp"></span>';
	echo '</div>';
	
	if ( $can_edit && in_array( $revision->post_status, array( 'pending', 'future' ) ) ) {
		echo '<div id="timestampdiv" class="hide-if-js clear">';
		
		global $post;	// touch_time function requires this as of WP 2.8
		$buffer_post = $post;
		$post = $revision;
		touch_time(($action == 'edit'),1,4);
		$post = $buffer_post;
		
		echo '</div>';
		
		?>
		<div id="rvy_revision_edit_secondary_div" style="display:none;">
		<input name="rvy_revision_edit" type="submit" class="button-primary" id="rvy_revision_edit_secondary" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
		</div>
		<?php
	}
	echo '</div>';

echo '</td></tr>';
echo '</table>';
	
	echo '
	<div id="poststuff" class="metabox-holder rvy-editor">
	<div id="post-body">
	<div id="post-body-content">
	';
	
	// title stuff
	echo '
	<div id="titlediv rvy-title-div">
	<div id="titlewrap">
		<label class="screen-reader-text" for="title">';
		
	echo( __awp('Title') );
	$disabled = ( $can_edit ) ? '' : 'disabled="disabled"';
	
	echo '
	</label><input type="text" name="post_title" size="30" tabindex="1" value="';
	
	echo esc_attr( htmlspecialchars( $revision->post_title ) );
	
	echo '" id="title" ' . $disabled . '/></div></div>';

		
	// post content
	$id = ( user_can_richedit() ) ? 'postdivrich' : 'postdiv';
	echo "<div id='$id' class='postarea rvy-postarea'>";
	$content = apply_filters( "_wp_post_revision_field_post_content", $revision->post_content, 'post_content' );
	
	if ( ! user_can_richedit() )
		$content = htmlentities($content);

	the_editor($content, 'content', 'title', false);
	echo '</div>';
	
    do_action( 'rvy-revisions_sidebar' );

	if ( $can_edit ) {
?>
<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
<input name="rvy_revision_edit" type="submit" class="button-primary" id="rvy_revision_edit" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
<?php
	}
		
	echo '
	</div>
	</div>
	</div>
	';
} else 
	echo '</tr></table>';
?>

<?php do_action( 'rvy-revisions_meta_boxes' ); ?>

</form>

<div class="ie-fixed">
<?php if ( 'diff' == $action ) : ?>
<?php
if ( strtotime($left_revision->post_modified) > strtotime($right_revision->post_modified) ) {
	$temp = $left_revision;
	$left_revision = $right_revision;
	$right_revision = $temp;
}

$title_left = sprintf( __('Older: modified %s', 'revisionary'), RevisionaryAdmin::convert_link( rvy_post_revision_title( $left_revision, true, 'post_modified' ), 'revision', 'manage' ) );

$title_right = sprintf( __('Newer: modified %s', 'revisionary'), RevisionaryAdmin::convert_link( rvy_post_revision_title( $right_revision, true, 'post_modified' ), 'revision', 'manage' ) );


$identical = true;
foreach ( _wp_post_revision_fields() as $field => $field_title ) :
	if ( ( 'post_content' == $field ) && ( ! $action || ( 'view' == $action ) ) )
		continue;
		
	if ( 'diff' == $action ) {
		$left_content = apply_filters( "_wp_post_revision_field_$field", $left_revision->$field, $field );
		$right_content = apply_filters( "_wp_post_revision_field_$field", $right_revision->$field, $field );
		
		if ( rvy_get_option('diff_display_strip_tags') ) {
			$left_content = strip_tags($left_content);
			$right_content = strip_tags($right_content);
		}
		
		if ( !$content = wp_text_diff( $left_content, $right_content, array( 'title_left' => $title_left, 'title_right' => $title_right ) ) )
			continue; // There is no difference between left and right
		$identical = false;
	} elseif ( $revision ) {
		if ( $revision && ( 'post_title' == $field ) ) {
			if ( 'revision' != $revision->post_type )	// no need to redisplay title
				continue;
			
			if ( $revision->post_title == $rvy_post->post_title )
				continue;
		}
		
		$content = apply_filters( "_wp_post_revision_field_$field", $revision->$field, $field );
	}
	
	if ( ! empty($content) ) :?>
	<div id="revision-field-<?php echo $field; ?>">
		<p class="rvy-revision-field"><strong>
		<?php 
		echo esc_html( $field_title ); 
		?>
		</strong></p>
		
		<div class="pre clear"><?php echo $content; ?></div>
	</div>
	<?php endif;

	$title_left = '';
	$title_right = '';
	
endforeach;

endif;  // 'diff' == $action


if ( 'diff' == $action && $identical ) :
	?>

	<div class="updated"><p><?php _e( 'These revisions are identical.' ); ?></p></div>

	<?php

endif;

?>

</div>

<br class="clear" /><br />

<?php
if ( $is_administrator = is_content_administrator_rvy() ) {
	global $wpdb;
	$results = $wpdb->get_results( "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = '$rvy_post->ID' GROUP BY post_status" );
	
	$num_revisions = array( 'inherit' => 0, 'pending' => 0, 'future' => 0 );
	foreach( $results as $row )
		$num_revisions[$row->post_status] = $row->num_posts;
		
	$num_revisions = (object) $num_revisions;
}

$status_links = '<ul class="subsubsub">';
foreach ( array_keys($revision_status_captions) as $_revision_status ) {
	$post_id = ( ! empty($rvy_post->ID) ) ? $rvy_post->ID : $revision_id;
	$link = "admin.php?page=rvy-revisions&amp;revision={$post_id}&amp;revision_status=$_revision_status";
	$class = ( $revision_status == $_revision_status ) ? ' class="rvy_current_status rvy_select_status"' : 'class="rvy_select_status"';

	switch( $_revision_status ) {
		case 'inherit':
			$status_caption = __( 'Past Revisions', 'revisionary' );
			break;
		case 'pending':
			$status_caption = __( 'Pending Revisions', 'revisionary' );
			break;
		case 'future':
			$status_caption = __( 'Scheduled Revisions', 'revisionary' );
			break;
	}
	
	if ( $is_administrator ) {
		$label = __( '%1$s <span class="count"> (%2$s)</span>', 'revisionary' );
		$status_links .= "<li $class><a href='$link'>" . sprintf( _nx( $label, $label, $num_revisions->$_revision_status, $label ), $status_caption, number_format_i18n( $num_revisions->$_revision_status ) ) . '</a></li>';
	} else
		$status_links .= "<li $class><a href='$link'>" . $status_caption . '</a></li>';
}
$status_links .= '</ul>';

echo $status_links;
	
$current_id = ( isset($revision_id) ) ? $revision_id : 0;
$args = array( 'format' => 'form-table', 'parent' => true, 'right' => $right, 'left' => $left, 'current_id' => $current_id );

$count = rvy_list_post_revisions( $rvy_post, $revision_status, $args );
if ( $count < 2 ) {
	echo( '<br class="clear" /><p>' );
	printf( __( 'no %s revisions available.', 'revisionary'), strtolower($revision_status_captions[$revision_status]) );
	echo( '</p>' );
}

?>

</div>