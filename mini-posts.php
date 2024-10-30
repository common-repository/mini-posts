<?php
/*
Plugin Name: MiniPosts
Plugin URI: http://doocy.net/mini-posts/
Description: An approach to "asides", or small posts. Allows you to mark entries as "mini" posts and handle them differently than normal posts. <strong>Requires WordPress 1.5.</strong>
Version: 0.5.2
Author: Morgan Doocy
Author URI: http://doocy.net/
*/

/*
MiniPosts - Small posts, or "asides," plugin for WordPress (http://wordpress.org).
Copyright (C) 2005  Morgan Doocy

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/*
NOTE: This release is ALPHA-level software. It is known to be feature-incomplete and/or partially functional.

Changelog
=========
0.1a   - Initial release.
0.2    - Corrected load_plugin_textdomain() domain.
0.3    - Updated to accommodate WP pages.
0.4    - Updated to accommodate feeds; added option to filter mini posts from feeds.
0.5    - Added support for 0-, 1-, and n-comment formatting to get_mini_posts().
        (WARNING: The argument sequence of get_mini_posts() has changed to accommodate this.)
0.5.1  - Added 'save_post' hook
0.5.2  - Fixed label id for "This is a mini post" checkbox
       - Added pagination on Options page
       - Changed all references to the plugin's filename to basename(__FILE__), to avoid file naming issues
       - Added aliases to JOIN and WHERE clauses to avoid collisions with other plugins
        (thanks, Jerome and Mark!)

*/

if (is_plugin_page()) {
	include(ABSPATH.'wp-blog-header.php');

	$paged_val = get_query_var('paged');
	$paged_val = $paged_val ? "&paged=$paged_val" : '';
	
	if (isset($_POST["update_options"])) {
		update_option('filter_mini_posts_from_loop', $_POST['filter_mini_posts_from_loop'] == 1 ? 1 : 0);
		update_option('suppress_autop_on_mini_posts', $_POST['suppress_autop_on_mini_posts'] == 1 ? 1 : 0);
		update_option('filter_mini_posts_from_feeds', $_POST['filter_mini_posts_from_feeds'] == 1 ? 1 : 0);
		echo '<div class="updated"><p><strong>' . __('Options saved.', 'MiniPosts') . '</strong></p></div>';
	}
	elseif (isset($_POST["update_miniposts"])) {
		$is_mini_post = $_POST["is_mini_post"];
		foreach ($posts as $post) {
			delete_post_meta($post->ID, '_mini_post');
			$setting = in_array($post->ID, $is_mini_post) ? 1 : 0;
			add_post_meta($post->ID, '_mini_post', $setting);
		}
		header("Location: options-general.php?page=" . basename(__FILE__) . "&miniposts_updated=true$paged_val");
		exit();
	}
	
	$ck_filter_mini_posts_from_loop = get_settings('filter_mini_posts_from_loop') == 1 ? 'checked="checked" ' : '';
	$ck_suppress_autop_on_mini_posts = get_settings('suppress_autop_on_mini_posts') == 1 ? 'checked="checked" ' : '';
	$ck_filter_mini_posts_from_feeds = get_settings('filter_mini_posts_from_feeds') == 1 ? 'checked="checked" ' : '';
	
	?>
	<div class="wrap">
	<h2><?php _e('Mini Post Options', 'MiniPosts') ?></h2>
	<form method="post" action="./options-general.php?page=<?php echo basename(__FILE__) . $paged_val; ?>">
		<table width="100%" cellspacing="2" cellpadding="5" class="editform">
			<tr valign="top">
				<th width="33%" scope="row"><?php _e('Show/hide:', 'MiniPost') ?></th>
				<td>
					<label for="filter_mini_posts_from_loop"><input type="checkbox" name="filter_mini_posts_from_loop" id="filter_mini_posts_from_loop" <?php echo $ck_filter_mini_posts_from_loop ?> value="1" /> <?php _e('Filter mini posts from the Loop', 'MiniPosts') ?></label><br />
					<label for="filter_mini_posts_from_feeds"><input type="checkbox" name="filter_mini_posts_from_feeds" id="filter_mini_posts_from_feeds" <?php echo $ck_filter_mini_posts_from_feeds ?> value="1" /> <?php _e('Filter mini posts from subscription feeds', 'MiniPosts') ?></label><br />
				</td>
			</tr>
			<tr valign="top">
				<th width="33%" scope="row"><?php _e('Auto-paragraphing:', 'MiniPosts') ?></th>
				<td><label for="suppress_autop_on_mini_posts"><input type="checkbox" name="suppress_autop_on_mini_posts" id="suppress_autop_on_mini_posts" <?php echo $ck_suppress_autop_on_mini_posts ?> value="1" /> <?php _e('Suppress auto-paragraphing on mini posts in the Loop', 'MiniPosts') ?></label></td>
			</tr>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Update Options', 'MiniPosts') ?> &raquo;" /></div>
	</form>
	</div>
	<?php
	
	if ($_GET["miniposts_updated"] == "true")	 {
		
	?>
	<div class="updated"><p><strong><?php _e('Mini posts updated.', 'MiniPosts') ?></strong></p></div>
	<?php
	
	}
	
	?>
	<div class="wrap">
	<h2><?php _e('Mini Post Manager', 'MiniPosts') ?></h2>
	<form method="post" action="./options-general.php?page=<?php echo basename(__FILE__); ?>&noheader=true<?php echo $paged_val; ?>">
	<table width="100%" cellpadding="3" cellspacing="3">
		<thead>
			<tr>
				<th scope="col"><?php _e('ID', 'MiniPosts') ?></th>
				<th scope="col"><?php _e('When', 'MiniPosts') ?></th>
				<th scope="col"><?php _e('Title', 'MiniPosts') ?></th>
				<th scope="col"><?php _e('MiniPost', 'MiniPosts') ?></th>
				<th scope="col"><?php _e('Categories', 'MiniPosts') ?></th>
				<th scope="col"><?php _e('Comments', 'MiniPosts') ?></th>
				<th scope="col"><?php _e('Author', 'MiniPosts') ?></th>
				<td colspan="3"></td>
			</tr>
		</thead>
		<tbody>
	<?php
	
	foreach($posts as $post)
	{
		start_wp();
		$checked = is_mini_post() ? 'checked="checked" ' : '';
		$class = ('alternate' == $class) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row"><?php echo $id ?></th>
				<td><?php the_time('Y-m-d \<\b\r \/\> g:i:s a'); ?></td>
				<td><?php the_title() ?><?php if ('private' == $post->post_status) _e(' - <strong>Private</strong>'); ?></td>
				<td><input type="checkbox" name="is_mini_post[]" id="is_mini_post[]" value="<?php echo $id ?>" <?php echo $checked ?>/></td>
				<td><?php the_category(','); ?></td>
				<td><a href="edit.php?p=<?php echo $id ?>&amp;c=1"><?php comments_number(__('0'), __('1'), __('%')) ?></a></td>
				<td><?php the_author() ?></td>
				<td><a href="<?php the_permalink(); ?>" rel="permalink" class="edit"><?php _e('View'); ?></a></td>
				<td><?php if (($user_level > $authordata->user_level) or ($user_login == $authordata->user_login)) { echo "<a href='post.php?action=edit&amp;post=$id' class='edit'>" . __('Edit') . "</a>"; } ?></td>
				<td><?php if (($user_level > $authordata->user_level) or ($user_login == $authordata->user_login)) { echo "<a href='post.php?action=delete&amp;post=$id' class='delete' onclick=\"return confirm('" . sprintf(__("You are about to delete this post \'%s\'\\n  \'OK\' to delete, \'Cancel\' to stop."), the_title('','',0)) . "')\">" . __('Delete') . "</a>"; } ?></td>
			</tr>
		<?php
	} ?>
		</tbody>
	</table>
	<div class="navigation">
		<?php
			// Remove 'miniposts_updated' value from query string, if present, so that
			// get_pagenum_link() doesn't perpetuate it.
			$_SERVER['REQUEST_URI'] = preg_replace('/&miniposts_updated=true/', '', $_SERVER['REQUEST_URI']);
			$_SERVER['REQUEST_URI'] = preg_replace('/\?miniposts_updated=true&/', '?', $_SERVER['REQUEST_URI']);
			$_SERVER['REQUEST_URI'] = preg_replace('/\?miniposts_updated=true/', '', $_SERVER['REQUEST_URI']);
		?>
		<div class="alignleft"><?php next_posts_link(__('&laquo; Previous Entries')) ?></div>
		<div class="alignright"><?php previous_posts_link(__('Next Entries &raquo;')) ?></div>
	</div>
	<div class="submit"><input type="submit" name="update_miniposts" value="<?php _e('Update Mini Posts', 'MiniPosts') ?> &raquo;" /></div>
	</form>
	</div>
	<?php

} else {
	
	load_plugin_textdomain('MiniPosts');
	
	add_option('filter_mini_posts_from_loop', 1);
	add_option('suppress_autop_on_mini_posts', 1);
	add_option('filter_mini_posts_from_feeds', 0);
	
	// Auto-install: If there are no previous meta values for '_mini_post', automatically add one for each post
	if (0 == $wpdb->get_var("SELECT count(meta_value) FROM $wpdb->postmeta WHERE meta_key = '_mini_post'")) {
		if ($posts = $wpdb->get_results("SELECT * FROM $wpdb->posts")) {
			foreach ($posts as $post) {
				add_post_meta($post->ID, '_mini_post', '0');
			}
		}
	}
	
	function is_mini_post() {
		global $post;
		return (bool) get_post_meta($post->ID, '_mini_post', true);
	}
	
	function get_mini_posts($format = '%post% %commentcount% %permalink%', $permalink_text = '', $zero_comments = '', $one_comment = '', $more_comments = '', $limit = '') {
		global $wpdb;
		
		if ('' != $limit) {
			$limit = (int) $limit;
			$limit = ' LIMIT '.$limit;
		}
		
		// Determine whether a comment format was specified
		if ('' == $zero_comments && '' == $one_comment && '' == $more_comments)
			$comment_count_format = false;
		else
			$comment_count_format = true;
		
		// If only the 'zero' format is specified, use the same for all three
		if ('' != $zero_comments && '' == $one_comment && '' == $more_comments)
			$one_comment = $more_comments = $zero_comments;
		
		// Make $permalink_text and comment count formats coincide with $format,
		// resolving any conflicts.
		if ('' != $format) {
			if (strstr($format, '%commentcount%') && !$comment_count_format) {
				$zero_comments = $one_comment = $more_comments = '(%s)';
				$comment_count_format = true;
			}
			elseif (!strstr($format, '%commentcount%') && $comment_count_format)
				$format = "$format %commentcount%";
			if (strstr($format, '%permalink%') && '' == $permalink_text)
				$permalink_text = '#';
			elseif (!strstr($format, '%permalink%') && '' != $permalink_text)
				$format = "$format %permalink%";
			if (!strstr($format, '%post%'))
				$format = "%post% $format";
		} else {
			if ('' != $permalink_text && $comment_count_format)
				$format = '%post% %commentcount% %permalink%';
			elseif ('' == $permalink_text && $comment_count_format)
				$format = '%post% %commentcount%';
			elseif ('' != $permalink_text && !$comment_count_format)
				$format = '%post% %permalink%';
			elseif ('' == $permalink_text && !$comment_count_format)
				$format = '%post%';
		}
		
		if ($comment_count_format && $commentcounts = $wpdb->get_results("SELECT ID, COUNT($wpdb->comments.comment_post_ID) AS comment_count FROM $wpdb->comments INNER JOIN $wpdb->posts ON ($wpdb->comments.comment_post_ID = $wpdb->posts.ID) INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->postmeta.meta_key = '_mini_post' AND $wpdb->postmeta.meta_value = '1' $exclusions GROUP BY $wpdb->posts.ID")) {
			foreach ($commentcounts as $commentcount) {
				if ($commentcount > 0) {
					$minipost_commentcounts["$commentcount->ID"] = $commentcount->comment_count;
				}
			}
		}
		
		$now = current_time('mysql');
		
		if ($miniposts = $wpdb->get_results("SELECT ID, post_date, post_content, post_title FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE post_date < '$now' AND post_status = 'publish' AND $wpdb->postmeta.meta_key = '_mini_post' AND $wpdb->postmeta.meta_value = '1' ORDER BY post_date DESC" . $limit)) {
			foreach ($miniposts as $minipost) {
				if ($minipost->post_date != '0000-00-00 00:00:00') {
					$url  = get_permalink($minipost->ID);
					$title = $minipost->post_title;
					if ($title) {
						$title = strip_tags($title);
					} else {
						$title = $minipost->ID;
					}
					$text = $minipost->post_content;
					$text = wptexturize($text);
					$title_text = wp_specialchars($title, 1);
					
					if ('' != $permalink_text) {
						$permalink = "<a class=\"minipost_permalink\" href=\"$url\" title=\"$title_text\">$permalink_text</a>";
					}
					
					if ($comment_count_format) {
						$count = isset($minipost_commentcounts["$minipost->ID"]) ? $minipost_commentcounts["$minipost->ID"] : 0;
						if ($count == 0)
							$commentcounttext = $zero_comments;
						elseif ($count == 1)
							$commentcounttext = $one_comment;
						else
							$commentcounttext = $more_comments;
						$commenturl = "$url#comments";
						$commentcount = sprintf("<a class=\"minipost_commentlink\" href=\"$commenturl\" title=\"Comments for '$title_text'\">$commentcounttext</a>", $count);
					}
					
					$meta = str_replace('%permalink%', $permalink, $format);
					$meta = str_replace('%commentcount%', $commentcount, $meta);
					
					$text = str_replace('%post%', $text, $meta);
					
					echo "\t<li>$text</li>\n";
				}
			}
		}
	}
	
	function mini_posts_join($text) {
		global $wpdb, $pagenow;
		
		if (!is_plugin_page() && $pagenow != 'post.php' && $pagenow != 'edit.php' && get_settings('filter_mini_posts_from_loop') && !is_single() && !is_archive() && !is_page() && (is_feed() && get_settings('filter_mini_posts_from_feeds') || !is_feed()) && !stristr($text, "wp_postmeta")) {
			$text .= " LEFT JOIN $wpdb->postmeta AS miniposts_meta ON ($wpdb->posts.ID = miniposts_meta.post_id)";
		}
		
		return $text;
	}
	
	function mini_posts_where($text) {
		global $user_ID, $user_level, $wpdb, $pagenow;
		
		if (!is_plugin_page() && $pagenow != 'post.php' && $pagenow != 'edit.php' && get_settings('filter_mini_posts_from_loop') && !is_single() && !is_archive() && !is_page() && (is_feed() && get_settings('filter_mini_posts_from_feeds') || !is_feed())) {
			$text .= " AND (miniposts_meta.meta_key = '_mini_post' AND miniposts_meta.meta_value = 0)";
		}
		
		return $text;
	}
	
	if (get_settings('suppress_autop_on_mini_posts')) {
		function mini_post_autop($pee, $br = 1) {
			if (!is_mini_post() || is_single()) {
				$pee = $pee . "\n"; // just to make things a little easier, pad the end
				$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
				// Space things out a little
				$pee = preg_replace('!(<(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|math|p|h[1-6])[^>]*>)!', "\n$1", $pee); 
				$pee = preg_replace('!(</(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|math|p|h[1-6])>)!', "$1\n", $pee);
				$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines 
				$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
				$pee = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "\t<p>$1</p>\n", $pee); // make paragraphs, including one at the end 
				$pee = preg_replace('|<p>\s*?</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace 
				$pee = preg_replace('!<p>\s*(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|hr|pre|select|form|blockquote|math|p|h[1-6])[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
				$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
				$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
				$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
				$pee = preg_replace('!<p>\s*(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|hr|pre|select|form|blockquote|math|p|h[1-6])[^>]*>)!', "$1", $pee);
				$pee = preg_replace('!(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|math|p|h[1-6])[^>]*>)\s*</p>!', "$1", $pee); 
				if ($br) $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
				$pee = preg_replace('!(</?(?:table|thead|tfoot|caption|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|math|p|h[1-6])[^>]*>)\s*<br />!', "$1", $pee);
				$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)!', '$1', $pee);
				$pee = preg_replace('!(<pre.*?>)(.*?)</pre>!ise', " stripslashes('$1') .  clean_pre('$2')  . '</pre>' ", $pee);
			}
			return $pee; 
		}
	}
	
	add_filter('posts_where', 'mini_posts_where');
	add_filter('posts_join', 'mini_posts_join');
	if (get_settings('suppress_autop_on_mini_posts')) {
		remove_filter('the_content', 'wpautop');
		add_filter('the_content', 'mini_post_autop');
	}
	
	function mini_posts_checkbox() {
		global $postdata;
		
		$is_mini = get_post_meta($postdata->ID, '_mini_post', true);
		$check = $is_mini ? 'checked="checked" ' : '';
		
		echo '<fieldset><legend><a href="http://doocy.net/mini-posts/help/" title="Help with MiniPosts">' . __('MiniPosts', 'MiniPosts') . '</a></legend>';
		echo '<label for="is_mini_post"><input type="checkbox" name="is_mini_post" id="is_mini_post" value="1" '. $check . '/> '. __('This is a mini post', 'MiniPosts') . '</label></fieldset>';
	}
	
	function mini_update_post($id) {
		delete_post_meta($id, '_mini_post');
		$setting = (isset($_POST["is_mini_post"]) && $_POST["is_mini_post"] == "1") ? 1 : 0;
		add_post_meta($id, '_mini_post', $setting);
	}
	
	function mini_admin_menu() {
		add_options_page(__('Mini Post Manager', 'MiniPosts'), __('MiniPosts', 'MiniPosts'), 5, basename(__FILE__));
	}
	
	add_action('edit_form_advanced', 'mini_posts_checkbox');
	add_action('simple_edit_form', 'mini_posts_checkbox');
	add_action('save_post', 'mini_update_post');
	add_action('edit_post', 'mini_update_post');
	add_action('publish_post', 'mini_update_post');
	add_action('admin_menu', 'mini_admin_menu');
}
?>
