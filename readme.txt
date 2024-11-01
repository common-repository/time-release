=== Plugin Name ===
Contributors: erigami
Tags: delay, automatic post, publish, schedule, time, queue, inactivity
Requires at least: 2.0.2
Tested up to: 2.8.6
Stable tag: 1.0.4

Queue up posts to be displayed after a period of inactivity. If you don't post during that period, the queued posts are published. 

== Description ==

With Time Release, you set your blog to publish a post after some period of inactivity. You edit and create Time Release posts in the usual way, but you mark it as “Time Release”. After a few days, if you haven’t posted any new stories, then the Time Release posts will start to be published.

= Features =
* Displays a single post after _n_ days of inactivity
* Posts with a date in the future aren't published until that date has passed
* Notifies author by email when a post has been published

Bugs should be reported in the comment section of [the author's page](http://www.piepalace.ca/blog/projects/time-release).

== Installation ==

1. Download and unzip.
2. Copy the `timerelease` directory into your `wp-content/plugins` directory.
3. Activate the "Time Release" plugin in your administrator panel.

== Use ==

For more detailed instructions, see [the author's page](http://www.piepalace.ca/blog/projects/time-release).

= To mark a post as Time Release =

1. Create a new post,
2. On the post edit page, check the box “Queue post for time release” in the “Time Release” sidebar widget,
3. Save the post as a draft. 

= Find Time Release posts = 

You can see your Time Release posts by going to the “Edit Posts” page of your blog. You’ll see a new column with a pill icon for a title. All Time Release posts have a little pill. Time Release posts that haven’t been published yet show a count to the number of days before the post is published.

= Control when Time Release posts are published =

Expand the "Settings" box in the administrative pages, and click on "Time Release". The settings page has controls for email notifications and the amount of time that must elapse before a queued post is published.


= Usage notes = 

1. Time Release publishes your queued posts after _n_ days of inactivity. If you create a new Time Release post, and it has been more than _n_ days since your last post, the post will appear within a day. 
2. Time Release publishes one post at a time. 
3. Time Release checks your inactivity twice daily. It sets the publication time to the time that it runs at. 

== Changelog ==

= 1.0.4 (A Dingo Ate My Baby) =
* Fixed URI of pill icon. Reported by Henrik Jernevad (http://principles.henko.net)
* Fixed form-variable collision with [Miniposts plugin](http://wordpress.org/extend/plugins/miniposts/).

= 1.0.3 (A Murder of Crows) =
* Fixed plugin URI. Reported by Mattias/mrhandley (http://www.spinell.se/)
* Fixed permissions problem with the options page. Some users reported seeing a message saying "You do not have sufficient permissions to access this page" when updating their options. Fixed by moving option processing into main source file.

= 1.0.1 (Mr Macavity's Good Deed) =
* Initial beta release.

= 1.0.0 =
* Alpha release (on [author's blog](http://piepalace.ca/blog))

