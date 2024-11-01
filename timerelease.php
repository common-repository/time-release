<?php
/*
Plugin Name: Time Release
Plugin URI: http://www.piepalace.ca/blog/projects/time-release
Description: Queue up posts to be displayed after a period of inactivity.
Version: 1.0.4 (A Dingo Ate My Baby)
Author: Erigami Scholey-Fuller
Author URI: http://piepalace.ca/blog/
*/

/*
Time Release - Post queuing plugin for WordPress (http://wordpress.org).
Copyright (C) 2009  erigami@piepalace.ca

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
Changelog
=========
1.0.0 - Alpha one.
1.0.1 - Beta one. Work around missing behaviours in Wordpress APIs. Fixed stupid bugs. 
1.0.3 - Fixed plugin URI. Reported by Mattias/mrhandley (http://www.spinell.se/)
      - Fixed permissions problem with the options page. Some users reported seeing a message saying
        "You do not have sufficient permissions to access this page" when updating their options. Fixed
        by moving option processing into main source file.
1.0.4 * Fixed URI of pill icon. Reported by Henrik Jernevad (http://principles.henko.net)
*/

define('TIME_RELEASE', "TimeRelease"); // Id for the wp_*_widget() fns
define('TIMERELEASE_SCHEDULE_HOOK', 'timerelease_autopublish_test'); // Name of the hook that we schedule


add_action('admin_menu', 'timerelease_admin');


/** Add a column to the post listing. */
function timerelease_column_headers($defaults) {
    // We want to appear after the post title. We use the following to 
    //  futz the ordering. 
    $r = array();
    foreach ($defaults as $key => $value) {
        $r[$key] = $value;
        if ($key == 'title') {
            $r[TIME_RELEASE] = '<img title="Posts queued with Time Release" src="' . plugins_url('time-release/ui/pill_header.png') . '"/>';
        }
    }

    timerelease_column_compute_futures();

    return $r;
}

/** Compute the expected number of days before autoposting. Leaves two global variables:
 * $timerelease_futures - A hash of posts and when they will be posted.
 * $timerelease_immediate - A boolean set to TRUE if any new timerelease post will be posted immediately.
 * $timerelease_last - The *nix timestamp of any new timerelease post. 
 */
function timerelease_column_compute_futures($postCount = 30) {
    global $timerelease_futures, $timerelease_immediate, $timerelease_last;

    // Array of expected publication time. 
    // Keyed by post id. 
    // Value is a two element array: [0] is the number of days until posting, [1] is *nix timestamp of posting
    $timerelease_futures = array(); 

    $delay = get_option('timerelease_delay') * 3600 * 24;
    $posts = get_posts('numberposts=' . $postCount . '&orderby=date&order=ASC&meta_key=_timerelease_isa&meta_value=1&post_status=draft');

    $now = time();
    $next = strtotime(get_lastpostdate('blog')) + $delay;
    $delayed = array(); // Posts that have a future publication date
    foreach ($posts as $post) {
        if (strtotime($post->post_date) > $next) {
            $delayed[] = $post;
            continue;
        }

        while (sizeof($delayed) > 0) {
            // Handle posts that appear after a certain time
            $my_date = strtotime($post->post_date);
            if (strtotime($delayed[0]->post_date) < $my_date) {
                $timerelease_futures[$delayed[0]->ID] = array(ceil(($next - $now) / (3600 * 24)), $next);
                $next += $delay;
                array_shift($delayed);
            }
        }

        $timerelease_futures[$post->ID] = array(ceil(($next - $now) / (3600 * 24)), $next);
        $next += $delay;
    }

    while (sizeof($delayed) > 0) {
        // Eat up any unhandled future posts
        $my_date = strtotime($delayed[0]->post_date);

        if ($my_date < $next) {
            $timerelease_futures[$delayed[0]->ID] = array(ceil(($next - $now) / (3600 * 24)), $next);
            $next += $delay;
        } else {
            $timerelease_futures[$delayed[0]->ID] = array(ceil(($my_date - $now) / (3600 * 24)), $my_date);
            $next = $my_date + $delay;
        }

        array_shift($delayed);
    }

    // Determine if we're posting immediately
    $timerelease_immediate = ($next < $now);

    $timerelease_last = $next;
}


/** Add a column to the post listing. */
function timerelease_column($column_name, $id) {
    global $timerelease_futures;

    if ($column_name != TIME_RELEASE) {
        return;
    }

    $isa = get_post_meta($id, '_timerelease_isa', true);
    if ($isa == '') {
        return;
    }

    // Are we posted?
    if ('publish' == get_post_status($id)) {
        print '<img title="Posted by Time Release" src="' . plugins_url('time-release/ui/pill_posted.png') . '"/>';
        return;
    }

    $in = $timerelease_futures[$id][0];
    $date = date("F j, Y", $timerelease_futures[$id][1]);
    print "<span title=\"Will publish on $date\"><img src=\"" . plugins_url('time-release/ui/pill_draft.png') . "\"/><sub style=\"color: #999;\">$in</sub></span>";
}

/** Function run to update the admin ui. */
function timerelease_admin() {
    add_submenu_page('options-general.php', __('Time Release'), __('Time Release'), 'switch_themes', __FILE__, 'timerelease_admin_options');

    add_meta_box('timerelease-div', __('Time Release'), 'timerelease_post_sidebar', 'post', 'side' );

    if (get_option('timerelease_post_column')) {
        add_filter('manage_posts_columns', 'timerelease_column_headers');
        add_action('manage_posts_custom_column', 'timerelease_column', 10, 2); 
    }

    wp_enqueue_style('timerelease-admin-style', plugins_url( 'time-release/ui/admin.css'));
}

function timerelease_admin_options() {
	if (isset($_POST["timerelease_update_options"])) {
	    $errors = array();
	
	    if (ctype_digit($_POST['timerelease_delay'])) {
	        update_option('timerelease_delay', $_POST['timerelease_delay']);
	    }
	    else {
	        $errors[] = __("Delay must be a number.");
	    }
	
	    delete_option('timerelease_notify');
	    if (isset($_POST['timerelease_notify'])) {
	        add_option('timerelease_notify', 'email');
	    }
	    
	    delete_option('timerelease_post_column');
	    if (isset($_POST['timerelease_post_column'])) {
	        add_option('timerelease_post_column', '1');
	    }
	    
	    if (sizeof($errors) == 0) {
	        echo '<div class="updated"><p><strong>' . __('Options saved.', 'TimeRelease') . '</strong></p></div>';
	    } else {
	        echo '<div class="error"><p><strong>';
	        echo __('Options partially saved. Error in submitted data: ', 'TimeRelease');
	        foreach ($errors as $e) {
	            echo '<li>';
	            echo $e;
	            echo "</li>\n";
	        }
	        echo '</strong></p></div>';
	    }
	
	    // Our settings change may have triggered a post to be posted
	    timerelease_on_cron();
	}

    include(dirname(__FILE__) . '/options.php');
}

/** Sidebar widget for post creation/edit. */
function timerelease_post_sidebar() {
    global $timerelease_immediate, $timerelease_last;

    timerelease_column_compute_futures();

    $is_tr = get_post_meta($_REQUEST['post'], '_timerelease_isa', true);
    $check = $is_tr ? 'checked="checked" ' : '';
    $next = "";
    if ($timerelease_immediate) {
        $next = __("Will be posted immediately.", TIME_RELEASE);
    } else {
        $next = __("Will be posted on ", TIME_RELEASE) . date("F j, Y", $timerelease_last);
    }
    ?>
<label for="is_timerelease_post">
  <input type="checkbox" name="is_timerelease_post" id="is_timerelease_post" value="1" 
        <?php echo $check; ?> />
    <span title="<?php echo $next; ?>"><?php print __('Queue post for time release', TIME_RELEASE); ?></span>
</label>
<input type="hidden" name="timerelease_nonce" value="<?php 
    print wp_create_nonce(TIME_RELEASE);
?>">
<?php
}




add_action('save_post', 'timerelease_update_post');
add_action('edit_post', 'timerelease_update_post');
add_action('publish_post', 'timerelease_update_post');

/** Update a post based on the post creation/edit widget. */
function timerelease_update_post($id) {
    if ( current_user_can('edit_post', $id) 
        && isset($_POST["timerelease_nonce"]) 
        && wp_verify_nonce($_POST['timerelease_nonce'], TIME_RELEASE)
    ) {
        $setting = (isset($_POST["is_timerelease_post"]) && $_POST["is_timerelease_post"] == "1") ? 1 : null;
        delete_post_meta($id, '_timerelease_isa');
        if ($setting) {
            add_post_meta($id, '_timerelease_isa', 1, true);
        }
    }

    return $post_id;
}


/** Display the dashboard widget */
function timerelease_dashboard_widget() {
    global $timerelease_futures, $timerelease_immediate, $timerelease_last;

    // Show a warning if our cron job isn't set.
    if (false === wp_get_schedule(TIMERELEASE_SCHEDULE_HOOK)) {
        print '<div class="error">Time Release task is not scheduled to run. Try removing the plugin and re-adding it.</div>';
    }

    timerelease_column_compute_futures();

    // Normal message.
    print 'Time Release is configured to automatically publish every ' . get_option('timerelease_delay', '{unset}') . ' days. ';

    if ($timerelease_immediate) {
        print 'Next post queued for Time Release will be published immediately. ';
    }

    // Show the pending posts
    $posts = get_posts('numberposts=5&orderby=date&order=ASC&meta_key=_timerelease_isa&meta_value=1&post_status=draft');
    if (sizeof($posts) > 0) {
        print '<p style="margin-bottom: 0px;">Waiting to be published:</p><ul>';

        foreach ($posts as $post) {
            $id = $post->ID;

            $edit = get_edit_post_link($id);
            $url = get_permalink($id);
            $title = htmlentities(get_the_title($id));
            $date = date("F j, Y", $timerelease_futures[$id][1]);

            print "<li style=\"padding-left: 2ex;\"><h4 style=\"font-weight: normal;\"><a href=\"$edit\">$title</a> <span style=\"color: #999; font-size: 11px; margin-left: 3px;\" title=\"Will be published on or around $date\">$date</h4></li>";
        }

        print '</ul>';
    }

    // Show the published posts
    $history = get_option('_timerelease_history', null);
    if ($history != null && is_array($history)) {
        print '<p style="margin-bottom: 0px;">Published by Time Release:</p><ul>';

        $history = array_slice($history, -5);
        foreach ($history as $publication) {
            $id = $publication['id'];
            $time = $publication['posted'];
            $email = $publication['notified'];

            $edit = get_edit_post_link($id);
            $url = get_permalink($id);
            $title = htmlentities(get_the_title($id));
            $printableTime = date("F j, Y");

            $exactTime = date("Y-m-d H:i:s");
            $detailed = "Published at $exactTime. ";
            if ($publication['notified']) {
                $detailed .= "Notification email sent.";
            }

            print "<li style=\"padding-left: 2ex;\"><h4 title=\"$detailed\" style=\"font-weight: normal;\"><a href=\"$edit\">$title</a> <span style=\"font-size: smaller; color: #999999;\">Published on $printableTime</span></h4></li>";
        }

        print '</p>';
    }
} 

// Create the function use in the action hook
function timerelease_add_dashboard_widget() {
	wp_add_dashboard_widget('timerelease_dashboard_widget', 'Time Release', 'timerelease_dashboard_widget');
} 

add_action('wp_dashboard_setup', 'timerelease_add_dashboard_widget' );



// Add hooks for activation and deactivation
register_activation_hook(__FILE__, 'timerelease_activation');
add_action(TIMERELEASE_SCHEDULE_HOOK, 'timerelease_on_cron');

function timerelease_activation() {
	wp_schedule_event(time(), 'twicedaily', TIMERELEASE_SCHEDULE_HOOK);
    add_option('timerelease_delay', 7);
    add_option('timerelease_notify', 'email');
    add_option('timerelease_post_column', 1);
}

register_deactivation_hook(__FILE__, 'timerelease_deactivation');

function timerelease_deactivation() {
	wp_clear_scheduled_hook(TIMERELEASE_SCHEDULE_HOOK);
}


/** Function that checks our settings and publishes the appropriate post. */
function timerelease_on_cron() {
    // Find the date of the last publish
    $lastPost = strtotime(get_lastpostdate('blog'));
    #print "date: " . $lastPost . " " . date("r", $lastPost);
    $days = (int)get_option('timerelease_delay', 7);
    $now = time();
    #print "<br/>now: $now " . date("r", $now);
    $next = $lastPost + ($days * 3600 * 24);
    #print "<br/>next: $next " . date("r", $next);

    if ($now >= $next) {
        // Update
        $posts = get_posts('numberposts=1&orderby=date&order=ASC&meta_key=_timerelease_isa&meta_value=1&post_status=draft');

        if (sizeof($posts) > 0) {
            # Set the publication date to 'now'
            $now = date('Y-m-d H:i:s');
            $now_gmt = gmdate('Y-m-d H:i:s');
            $update = array(
                'ID' => $posts[0]->ID,
                'edit_date' => $now, //< Necessary to prevent post_date_gmt from being overwritten
                'post_date' => $now,
                'post_date_gmt' => $now_gmt,
            );

            # The post may not have a slug. Create one. 
            if (strlen($posts[0]->post_name) < 1) {
                $update['post_name'] = sanitize_title($posts[0]->post_title, date('Y-m-d'));
            }

            wp_update_post($update);

            # publish the post
            wp_publish_post($posts[0]->ID);

            # Email the user
            $emailSent = 0;
            if (!is_null(get_option('timerelease_notify', null))) {
                $user = get_userdata($posts[0]->post_author);
                if (isset($user->user_email)) {
                    $title = $posts[0]->post_title;
                    wp_mail($user->user_email, "Published \"$title\"", "Automatically published post \"$title\" after $days of postlessness.");
                    $emailSent = 1;
                }
            }

            // Update post history
            $history = get_option('_timerelease_history', array());
            $history[] = array('id' => $posts[0]->ID, 'posted' => time(), 'notified' => $emailSent);

            $history = array_slice($history, -20);// Prevent our history from growing too large

            if (get_option('_timerelease_history', null)) {
                update_option('_timerelease_history', $history);
            } else {
                add_option('_timerelease_history', $history);
            }
        }
    }
}
