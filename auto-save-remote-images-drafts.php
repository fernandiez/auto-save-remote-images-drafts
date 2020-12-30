<?php  
/*  
Plugin Name: Auto Save Remote Images (Drafts)
Plugin URI: https://github.com/fernandiez/auto-save-remote-images-drafts
Description: WordPress plugin for downloading automatically first remote image from a post and setting it as a featured image (when is saved as a draft or updated)
Version: 1.1.4
Author: Fernan Díez 
Author URI: http://www.fernan.com.es/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: auto-save-remote-images-drafts
Domain Path: /languages
Disclaimer: Please do not use this plugin to violate copyrights. Don't be evil.
*/

add_action('save_post', 'fetch_images');

function fetch_images( $post_ID )  
{	
	//Check to make sure function is not executed more than once on save
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
	return;

	if ( !current_user_can('edit_post', $post_ID) ) 
	return;

	//Check if there is already a featured image; if there is, then quit.
	if ( '' != get_the_post_thumbnail() )
	return;

	remove_action('save_post', 'fetch_images');	
		
	$post = get_post($post_ID);   

	$first_image = '';
	
	if(preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches)){
		$first_image = $matches [1] [0];
	}

	if (strpos($first_image,$_SERVER['HTTP_HOST'])===false)
	{
			
		//Fetch and Store the Image	
		$get = wp_remote_get( $first_image );
		$type = wp_remote_retrieve_header( $get, 'content-type' );
		$mirror = wp_upload_bits(rawurldecode(basename( $first_image )), '', wp_remote_retrieve_body( $get ) );
	
		//Attachment options
		$attachment = array(
		'post_title'=> basename( $first_image ),
		'post_mime_type' => $type
		);
		
		// Add the image to your media library and set as featured image
		$attach_id = wp_insert_attachment( $attachment, $mirror['file'], $post_ID );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $first_image );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		set_post_thumbnail( $post_ID, $attach_id );
	
		$updated = str_replace($first_image, $mirror['url'], $post->post_content);
	    
	    //Replace the image in the post
	    wp_update_post(array('ID' => $post_ID, 'post_content' => $updated));
	
	    // re-hook this function
	    add_action('save_post', 'fetch_images');		
	}
}

// Create menu section under Tools in WordPress admin
add_action( 'admin_menu', 'asri_drafts_admin_menu' );

// New section under Tools 
function asri_drafts_admin_menu() {
	add_management_page( 'ASRI Drafts', 'ASRI Drafts', 'manage_categories', 'auto-save-remote-images-drafts', 'content_asri_drafts_admin_menu' );
}

// Contents for the new section
function content_asri_drafts_admin_menu() {
	echo '<div class="wrap">';
	echo '<h1>';
	_e('Auto Save Remote Images (Drafts)', 'auto-save-remote-images-drafts');
	echo '</h1>';
	echo '<h3>';
	_e('WordPress plugin for downloading automatically first remote image from a post and setting it as a featured image (when is saved as a draft or updated)', 'auto-save-remote-images-drafts');
	echo '</h3>';
	echo '<h2>';
	_e('Description', 'auto-save-remote-images-drafts');
	echo '</h2>';

	echo'<p>';
	_e('WordPress plugin for downloading automatically <strong>first remote image from a post</strong> and setting it as a <strong>featured image</strong> (when the post is saved as a draft or updated).', 'auto-save-remote-images-drafts');
	echo'</p>';
	echo'<p>';
	_e('The purpose of this plugin is very simple: when a post is saved as a draft (not published) or updated (once published), it will fetch the first remote or external image that is referenced. The image that is retrieved is then attached to the post as the featured image. There are no additional settings to configure.', 'auto-save-remote-images-drafts');
	echo'</p>';
	echo'<h2>';
	_e('Usage', 'auto-save-remote-images-drafts');
	echo'</h2>';
	echo'<p>';
	_e('Avoid losing time in the process of setting featured image for each of your blog posts or page whenever you create new content from your WordPress blog site dashboard. This post retrieves the first remote or external image of your post and uses it as a featured image.', 'auto-save-remote-images-drafts');
	echo'</p>';
	echo'<p>';
	_e('If you maintain multiple blogs, just copy paste the post contents from one blog to the other and this plugin will automatically save the first external image to the second blog. There is no need to re-upload images to multiple sites.', 'auto-save-remote-images-drafts');
	echo'</p>';
	echo'<p>';
	_e('This plugin can also retrieve and save images from sites like Flickr, Facebook, Picasa, or any other image stock site once you have added it in your post editor just saving that single post. Remember that hotlinking is not a good practise.', 'auto-save-remote-images-drafts');
	echo'</p>';
	echo'<p>';
	_e('No longer overwriting a post existing featured image function added.', 'auto-save-remote-images-drafts');
	echo'</p>';
	echo'<p>';
	_e('Please do not use this plugin to violate copyrights. Don’t be evil.', 'auto-save-remote-images-drafts');
	echo'</p>';
}

?>
