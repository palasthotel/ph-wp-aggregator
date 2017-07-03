=== Aggregator ===
Contributors: palasthotel, edwardbock
Donate link: http://palasthotel.de/
Tags: aggregator, seo, javascript
Requires at least: 4.1
Tested up to: 4.8
Stable tag: 2.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl

Aggregates Javascript files to a footer and header file.

== Description ==

It's best practise to have only a very few page requests. This plugin aggregates all your local JavaScript files to two requests only.


== Installation ==

1. Upload `aggragator-wordpress.zip` to the `/wp-content/plugins/` directory
1. Extract the Plugin to a `aggregator` Folder
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 2.1 =
* additional attributes for script tags

= 2.0.4 =
* localize scripts on wp_print_scripts action fix

= 2.0.3 =
* main php file rename
* external scripts with https:// and // fix aggregation fix

= 2.0.2 =
* typos fixed (hook name and html comments)

= 2.0.1 =
* rerender on javascript content file changes

= 2.0 =
* we can handle different aggregation combinations now
* scripts need to be rerendered
* Hook `ph_aggregator_ignore` is marked as deprecated, please make sure to update your code to use the new hook `aggregator_ignore`, which provides a js handle argument.

= 1.1.4 =
* added try catch blocks around each javascript file to prevent script execution after an error

= 1.1.3 =
* wp_enqueue with aggregated files time as version

= 1.1.2 =
* get correct working theme directory

= 1.1.1 =
* default to uploads
* theme slug for aggregated scripts

= 1.1 =
* aggregated location configuratable
* object orientation implemented

= 1.0.8 =
* localization data for aggregated scripts fix

= 1.0.7 =
* dequeue wrong scripts fix

= 1.0.6 =
* new rules

= 1.0.5 =
* aggregated folder in plugin deprecated

= 1.0.4 =
* aggregation to theme directory

= 1.0.3 =
* when to aggregate optimization

= 1.0.2 =
* aggregate to subfolder to prevent deployment issues

= 1.0.1 =
* Separate logged in and logged out aggregation

= 1.0 =
* First release
* aggregates JavaScript files


== Upgrade Notice ==

* you will have to reactivate the plugin because main php file was renamed in version 2.0.3


== Arbitrary section ==

