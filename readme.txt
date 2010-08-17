=== feedgator ===
Contributors: imacfuzzy
Donate link: http://www.brownlowdown.net/webdev/feedaggregator
Tags: rss,atom,feed,list,group,arrange,aggregate,widget
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 1.0

Feed(Aggre)gator merges a group of RSS feeds into a single widgetized list.

== Description ==

Feed(Aggre)gator takes a list of RSS feeds and creates a single list of RSS items from those feeds to be displayed as a widget. You can select how many items you want to display and whether you want to show the entries chronologically or you want to show the latest entries from each feed listed.

You can also opt to display the date associated with the RSS item as well as the author of the item.

== Installation ==

1. Upload `feed-gator.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enter RSS feeds to aggregate and manage your display options through the 'Widgets' menu in WordPress
1. (optional) If you want to change the name that is displayed for certain authors, you can do so by modifying the $feedgator_item_author_arr in feed-gator.php. See feed-gator.php for more details.
1. (optional) If you want to assign a certain CSS class for list items from certain authors, you can do so by modifying the $feedgator_item_class_arr in feed-gator.php. See feed-gator.php for more details.

== Frequently Asked Questions ==

= I get a warning: "Warning: usort() [function.usort]: The argument should be an array in [...]\wp-content\plugins\feed-gator\feed-gator.php". What's wrong? =

Make sure that the URL's of your RSS feeds are correct in the widget options in the 'Widgets' menu.

== Screenshots ==

See website for screenshots.

== Donate ==

If you like the plugin, consider supporting the author: http://www.brownlowdown.net/webdev/feedaggregator#donate.

== Changelog ==

= 1.0 =
* Original release.

== Upgrade Notice ==

= 1.0 =
Original release.
