=== GlobalFeed ===
Contributors: mobius5150
Donate link: http://globalfeed.michaelblouin.ca
Tags: globalfeed,lifestream,feed,social,facebook,twitter,rss,youtube,sharing,integration
Requires at least: 3.3
Tested up to: 3.4.1
Stable tag: 0.1.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

GlobalFeed takes all of your social feeds, blog activity and rss feeds and easily brings it together into a single global feed.

== Description ==

GlobalFeed is designed to take content from various networks and bring them together in the one place that matters most, your blog!

GlobalFeed can automatically insert content into the WordPress loop to be displayed by your blog, or can be included using a shortcode or widget in your posts or on a sidebar.

In addition, GlobalFeed has been designed from the ground up to be easily extensible by third parties. This means that you can easily build your own feeds to either include on just your blog or to redistribute.

= Features =

*   Easily integrate with Facebook, Twitter, YouTube or any RSS feed. Flickr and DeviantArt are also in development for the next release.
*   Automatically detects URLs, Twitter usernames and Twitter hashtags in feeds, and links to the source.
*   Automatically integrates feeds with the main WordPress loop, but you may choose to display the content anywhere using shortcodes or widgets. (or all three!)
*   Designed from the ground up to be very easy to use and setup.
*   Automatically fetches regular updates from all configured feeds so you don't have to worry about being out-of-date.

= Note to developers =
GlobalFeed was built from the ground up to be easily extensible and customizable. It supports additional feeds and themes that are 
created in much the same ways as regular WordPress plugins and themes. We are working on a good developer documentation system, 
with tutorials on building GlobalFeed Feeds and Themes, however it is not available at the moment.

Starting in version 0.5, a directory will be created under /wp-content to store themes and feeds so they are not wiped out on upgrades.

If you are interested in developing GlobalFeed, or have a fix you would like to submit, please go to our bug reports page through the plugin and get in touch.

= Requirements =
PHP >= 5.2.2

== Installation ==

Installation and configuration is very quick and easy: 

1. Upload the `mb_globalfeed.zip` file using the WordPress Add New Plugin feature and install the plugin
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the GlobalFeed tab on the left and configure all of your social networks and feeds
1. Customize where and how GlobalFeed is displayed by adding shortcodes or widgets
1. Report any bugs you find using the bug icon in the top right-hand corner, and suggest any features you would like to see
1. Enjoy!

== Frequently Asked Questions ==

= What about site X? =

If GlobalFeed doesn't already include support for your social network of choice then please submit a feature request using the bug icon in the top right-hand corner of the GlobalFeed administration page. If your a plugin developer, consider checking out the documentation at: (globalfeed.michaelblouin.ca).

We handle feature requests on a popularity basis. The most demanded feature is the one that will be implemented the soonest.

In the meantime, many social networks allow you to access content via an RSS feed, and the builtin "MB RSS" feed in GlobalFeed can handle just that. The MB RSS feed can also handle as many RSS sources as you like.

= Help! Nothing is happening! =

If GlobalFeed is not working on your site please check to ensure that your server can access the social media sites you are trying to configure. 

For example, in order to use Facebook Connect, your server must be able to talk to https://graph.facebook.com/

If your server can talk to the external servers and the setup wizards are working fine but still nothing appears, check that the "Enable feeds in the WordPress loop" button is checked if you are trying to display your feeds
along with your blogs content, or that the shortcode is properly formed. If you are using the widget, ensure that you have selected at least one feed to be shown, and that the feed has content to display. ie, if you are trying to setup
Facebook, have you posted anything on Facebook?

Additionally, please note that GlobalFeed requires *a minimum of PHP 5.2*.

If the site still does not work, please either submit a bug report if you know what is happening, or go to the support page at http://globalfeed.michaelblouin.ca/

= New content takes a long time to show up, can I speed that up? =

New content should show up on your site within 10 minutes of post it by default. There are however some configuration options for choosing how long it takes.

If the content is taking much more time than the configured update interval, check that your WordPress installation and your server have the correct time and timezone information. You can also try setting the "Override post date/time if the time is in the future" 
option in the feed that is giving you difficulties.

If the content does not show up at all, please check the help for "Help! Nothing is Happening!" and/or submit a bug report.

= I really like the idea, but the admin is a little lacking =

We know! This is just the first release of GlobalFeed, but we've built it to be seriously configurable and do not at all believe in a "one size fits all" plugin, its just that you cannot currently set everything in the plugins' admin.
Because of that, we're working really hard on getting the admin pages more up to par and configurable while still keeping the plugin easy to use. We're also focusing on adding more feeds.

But thank you! If in the future you do find that GlobalFeed has developed into a good plugin please rate us on the WordPress plugins page!

== Changelog ==

= 0.1.3 =
- Added GlobalFeed option to open GlobalFeed-generated links in a new window.
- Fixed an issue that stopped GlobalFeed from updating automatically in certain hosting environments.
- Added better timezone handling for converting timezones to the local WP installs' 
- Fixed a scheduling issue where WP Cron jobs related to GlobalFeed were not rescheduled when the update interval was changed.
- Added in safety precautions to stop GlobalFeed from doing a bad write to the WP Cron schedule
- Fixed a scheduling issue where feeds were not always removed from WP Cron when deactivated
- Added a feed option to override the post time to the current WP Blog time if the post date is in the future (defaults to false)
- Added a manual update button to all feeds to force a scheduled update to occur immediately
- Fixed an issue where RSS Feeds selected from the multiple feeds found dialogue would not appear in the list until a page reload.
- Added an option in MB Twitter to allow importing of Retweets
- Restructured the internal layout of the Twitter feed and removed some new unused functions.

= 0.1.2 =
- MB RSS can be pointed to a webpage and will attempt to automatically discover its RSS feeds, and will then prompt the user if more than one feed is found.
- Added additional parsing capabilities to MB RSS to increase compatibility with feeds such as Tumblr and DeviantArt.
- Fixed a large number of notices that were appearing in older versions of PHP and that were breaking the plugin.
- Stopped the user from being redirected when pressing enter while a feed setup textbox was focused.
- Stopped MB RSS from displaying multiple feed removed messages when a feed as removed after adding one or more feeds.

= 0.1.1 =
- Stability improvements for feed setup screens, including much better support for the Facebook feed.
- Added reset to feed defaults for all feeds, and changed redo initial setup to show any existing settings when doing the setup.
- Added a feature that detects linked RSS feeds when a webpage url is given to MB RSS instead of an RSS feed. Works great for WordPress installations.
- Added the main 'Settings' tab with the ability to configure the feed update interval.

= 0.1 =
GlobalFeed was created to be an easy to use and extensible platform for adding external content to your blog.

== Upgrade Notice ==

= 0.1
This is the first plugin version.

== Screenshots ==

1. Easy configuration using quick and simple wizards.
2. Easy bug reports and feature requests.

== Planned Features ==

We have a number of planned features to be added to the plugin. Some of the are marked on the roadmap, and some are still in the air.

Looking for a new feature? Make sure you submit a feature request [here](http://globalfeed.michaelblouin.ca/feature-requests). (Even if it's already on this list -- it helps us prioritize)

= 0.2 =
*	More configuration options in both the plugin admin and all feed admin pages.
*       Tumblr and DeviantArt quickembeds in MB RSS.
*       Ability to just attach media (pictures/videos) to posts instead of embedding it directly in the content.
*	Better in-plugin support (explanation of options/features)
*	Flickr feed
*	Google+ feed

= 0.3 =
*	Feature pointers (IE: guided start up)
*       Launch plugin documentation and usage wiki on GlobalFeed website
*	Admin page generation helper functions for developers.

= 0.5 =
*	Better support for 3rd party feeds

= Winter Season 2012 =
Numerous Social Media APIs require applications to authenticate in order to fetch user
data, such as Facebook, Google+ and coming March 2013: Twitter. At GlobalFeed, we believe that 
the need for users to go and create their own applications on these services in order to 
get application IDs and secrets is not a positive user experience. That is why GlobalFeed 
will be rolling out a webservice based off of MichaelBlouin.ca that will be responsible
for fetching data directly from Social Media sources and will act as a middleman,
making it infinitely quicker and easier for users to access their social media content
from their blog. All users will have to do is give GlobalFeed permission to read 
their streams, and GlobalFeed will do the rest.

For privacy conscious users who would prefer GlobalFeed not have access to their information,
the GlobalFeed plugin will still offer users the option of inputting their own application
information and having their WordPress blog fetch data directly from these services instead
of using the GlobalFeed service.

If you have a positive or negative opinion or any concerns about this change, please let us know
on our Feature Requests page at: http://globalfeed.michaelblouin.ca/feature-requests