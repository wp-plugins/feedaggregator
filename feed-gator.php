<?php
/*
Plugin Name: feedgator
Plugin URI: http://www.brownlowdown.net/webdev/feedaggregator
Description: Feed Gator merges a group of RSS feeds into a single widgetized list.
Version: 1.0
Author: Tyler Crawford
Author URI: http://www.brownlowdown.net/
License: This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

$feedgator_item_author_arr = array(
	// To make custom names appear for entries from certain authors,
	// create a pair of strings with the author's handle and the name
	// you want to appear.
	// EXAMPLES:
	// "author handle" => "author's real name",
	// "tyler" => "Tyler Timbuktu",
);
$feedgator_item_class_arr = array(
	// To make custom classes appear for list items from certain authors,
	// create a pair of strings with the author's handle and the name of the class
	// you want to appear.
	// EXAMPLES:
	// "author handle" => "class name",
	// "tyler" => "wordpress",
);

add_action("widgets_init", array('feedgator', 'register'));

register_activation_hook( __FILE__, array('feedgator', 'activate'));
register_deactivation_hook( __FILE__, array('feedgator', 'deactivate'));


// The error message that is displayed on incompatibility.
define( "FEEDGATOR_ERROR_MESSAGE", "<p>This version of Wordpress does not support <b>feed-gator</b>.</p><p style='font-size:10px;'><em>WP does not have the Magpie class.</em></p>" );

/**
 * Helper function checks to see if author's name should be replaced in an item.
 */
function feedgator_item_author( $value ){
	global $feedgator_item_author_arr;
	return ( isset( $feedgator_item_author_arr[$value] ) ) ? $feedgator_item_author_arr[$value] : $value;
}

/**
 * Helper function checks to see if a list item's class should be replaced in an item.
 */
function feedgator_item_class( $value ){
	global $feedgator_item_class_arr;
	return ( isset( $feedgator_item_class_arr[$value] ) ) ? $feedgator_item_class_arr[$value] : strtolower(str_replace(" ","",$value));
}

/**
 *
 * Get the rss functionality we need from WP.
 *
 * @ return true if we find the functionality we need, false otherwise.
 */
function feedgator_check_wp_magpie(){
	// if the function already exists, do not do anything.
	if( function_exists("fetch_rss") ) return true;
	// if not, include rss.php from wp-includes
	require_once( ABSPATH . WPINC . '/rss.php' );
	
	return function_exists("fetch_rss");
}

/**
 *
 * Function used to compare items. Sorts chronologically.
 *
 */
function feedgator_item_compare_by_date( $a, $b ){
	if( $a->timestamp == $b->timestamp ) return 0;
	else return ( $a->timestamp < $b->timestamp ) ? 1 : -1;
}

/**
 *
 * Use Magpie "fetch_rss" to get the rss feeds.
 *
 */
function feedgator_get_rss_items( $rss_arr=array() ){

	// get the magpie lib
	if( !function_exists("fetch_rss") ) { echo "<b>ERROR:</b> fetch_rss() not found"; return array(); }

	// fetch all the feeds
	$feed_arr = array();
	foreach( $rss_arr as $i => $url ){
		$rss = fetch_rss( trim($url) );
		if( $rss ) $feed_arr[] = $rss;
	}
	// flatten the items into one array
	foreach( $feed_arr as $i => $rss ){
		foreach ($rss->items as $item) {
			$item_arr[] = new MagpieRSSItem($item);
		}
	}
	// now arrange the items according to timestamp
	usort( $item_arr, "feedgator_item_compare_by_date" );
	// and return the array
	return $item_arr;
}

/**
 *
 * Helper class digests rss items from different feed formats into one general class.
 *
 */
class MagpieRSSItem {
  var $title, $link, $date, $author, $timestamp, $class;
  function MagpieRSSItem( $arr ) {
    foreach( $arr as $key => $value ) {
	  switch( $key ) {
	    // title
		case 'title': $this->title = $value; break;
		// link
		case 'link': $this->link = $value; break;
		// description
		case 'description': $this->description = $value; break;
		// author
		case 'dc':
			$this->author = feedgator_item_author($value['creator']);
			$this->class = feedgator_item_class($value['creator']);
			break;
		case 'author_name':
			$this->author = feedgator_item_author($value);
			$this->class = feedgator_item_class($value);
			break;
		case 'media': // from flickr
			if( $this->author ) break; // don't copy over anything
			$this->author = feedgator_item_author($value['credit']);
			$this->class = feedgator_item_class($value['credit']);
			break;
		// date
		case 'date_timestamp':
			$this->timestamp = $value;
			$this->date = date("l, M j, Y", $value);
			break;
		case 'updated': // for atom feeds
			$this->timestamp = strtotime(substr($value,0,10));
			$this->date = date("l, M j, Y", strtotime(substr($value,0,10)));
			break;
		case 'pubdate':
			$this->timestamp = strtotime($value);
			$this->date = date("l, M j, Y", strtotime($value));
			break;
      }
	}
  }
}


class feedgator {
  function activate(){
    $data = array(
		'feedgator_numitems' => '4',
		'feedgator_widgettitle' => 'feedgator',
		'feedgator_rss_feeds' => '',
		'feedgator_no_title_text' => 'No Title',
		'feedgator_display_method' => 'latestentries',
		'feedgator_display_author' => '',
		'feedgator_display_date' => 'display_date',
	);
    if ( ! get_option('feedgator')){
      add_option('feedgator' , $data);
    } else {
      update_option('feedgator' , $data);
    }
  }
  function deactivate(){
    delete_option('feedgator');
  }
  function control(){  
  	// make sure that this version of WP supports feed-gator
	if( !feedgator_check_wp_magpie() ) {
		echo FEEDGATOR_ERROR_MESSAGE;
		return;
	}

	// get the saved options
  	$data = get_option('feedgator');
	?>
    
      <p><label>Title:<br /><input style="width:100%;" name="feedgator_widgettitle" type="text" value="<?php echo $data['feedgator_widgettitle']; ?>" /></label></p>
      <p><label>Number of items:<br /><input style="width:100%;" name="feedgator_numitems" type="text" value="<?php echo $data['feedgator_numitems']; ?>" /></label></p>
      <p><label>RSS feeds (on separate lines):<br /><textarea style="width:100%; font-size:10px; color:#555;" name="feedgator_rss_feeds"><?php echo str_replace(",","\n",$data['feedgator_rss_feeds']); ?></textarea></label></p>
      <p><label>Display method:<br /><select style="font-size:10px; width:100%;" name="feedgator_display_method"><option value="latestentries" <?php if($data['feedgator_display_method'] == 'latestentries') echo 'selected="selected"' ?>>by latest entry </option><option value="latestfromeachfeed" <?php if($data['feedgator_display_method'] == 'latestfromeachfeed') echo 'selected="selected"' ?>>latest from each feed </option></select></label></p>
      <p><label style="font-size:10px;">Text to display on no title:<br /><input style="font-size:10px; width:100%;" name="feedgator_no_title_text" type="text" value="<?php echo $data['feedgator_no_title_text']; ?>" /></label></p>
      <p><label style="font-size:10px;"><input type="checkbox" name="feedgator_display_author" value="display_author" <?php if($data['feedgator_display_author']) { ?>checked="checked"<?php } ?> style="margin-right:10px;" />Display author</label></p>
      <p><label style="font-size:10px;"><input type="checkbox" name="feedgator_display_date" value="display_date" <?php if($data['feedgator_display_date']) { ?>checked="checked"<?php } ?> style="margin-right:10px;" />Display date</label></p>
</label></p>
	<?php
	if (isset($_POST['feedgator_numitems'])){
		$data['feedgator_widgettitle'] = $_POST['feedgator_widgettitle'];
		$data['feedgator_numitems'] = (int)$_POST['feedgator_numitems'];
		$data['feedgator_display_method'] = $_POST['feedgator_display_method'];
		$data['feedgator_rss_feeds'] = str_replace(array("\n","&"),array(",","&amp;"),trim($_POST['feedgator_rss_feeds']));
		$data['feedgator_no_title_text'] = $_POST['feedgator_no_title_text'];
		$data['feedgator_display_author'] = $_POST['feedgator_display_author'];
		$data['feedgator_display_date'] = $_POST['feedgator_display_date'];
		update_option('feedgator', $data);
	}
  }
  function widget($args){
  	
  	$data = get_option('feedgator');
    echo $args['before_widget'];
    echo $args['before_title'].$data['feedgator_widgettitle'].$args['after_title'];

	if( !feedgator_check_wp_magpie() ) {
		echo FEEDGATOR_ERROR_MESSAGE;
		if( current_user_can('manage_options') ) echo "<p><a href='".get_bloginfo('wpurl')."/wp-admin/widgets.php'>Click here</a> to go to the widget control panel.</p>";
		echo $args['after_widget'];
		return;
	}

	// LATEST FROM EACH FEED
	if( $data['feedgator_display_method'] == 'latestfromeachfeed' ) {

		echo "<ul>";
		foreach( split(",",str_replace("&amp;","&",$data['feedgator_rss_feeds'])) as $i => $url ) {
			$items = feedgator_get_rss_items( array($url) );
			$count = 0;
			foreach( $items as $j => $item ) {
				if( $count++ >= $data['feedgator_numitems'] ) continue;
				if( !$item->title ) $item->title = $data['feedgator_no_title_text'];
				echo "<li class='".$item->class."'><strong><a href='".$item->link."' rel='".$url."' title='".$item->author."'>".$item->title."</a></strong>";
				if( $data['feedgator_display_author'] ) echo "<span class='meta feedgator-author'>".$item->author."</span>";
				if( $data['feedgator_display_date'] ) echo "<span class='meta feedgator-date'>".$item->date."</span>";
				echo "</li>";
			}
		}
		echo "</ul>";
		echo $args['after_widget'];
	
	} else { // LATEST ENTRIES

		// get the items to display
		$items = feedgator_get_rss_items( split(",",str_replace("&amp;","&",$data['feedgator_rss_feeds'])) );
		echo "<ul>";
		$count = 0;
		foreach( $items as $i => $item ){
			if( $count++ == $data['feedgator_numitems'] ) break;
			if( !$item->title ) $item->title = $data['feedgator_no_title_text'];
			echo "<li class='".$item->class."'><strong><a href='".$item->link."' rel='".$url."' title='".$item->author."'>".$item->title."</a></strong>";
			if( $data['feedgator_display_author'] ) echo "<span class='meta feedgator-author'>".$item->author."</span>";
			if( $data['feedgator_display_date'] ) echo "<span class='meta feedgator-date'>".$item->date."</span>";
			echo "</li>";
		}
	
		echo "</ul>";
		echo $args['after_widget'];

	}
	
  }
  function register(){
    register_sidebar_widget('feedgator', array('feedgator', 'widget'));
    register_widget_control('feedgator', array('feedgator', 'control'));
  }
}

?>