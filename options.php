<?php

?>
<div class="wrap">
<div id="icon-options-general" class="icon32">
    <br/>
</div>
<h2><?php _e('Time Release Options', 'TimeRelease') ?></h2>
<form method="post" action="">
    <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
        <tr valign="top">
            <th scope="row" style="width: 30%;">
                <?php _e('Days to wait before publishing Time Release posts', 'TimeRelease') ?>
            </th>
            <td>
                <label for="timerelease_delay">
                    <input type="text" name="timerelease_delay" id="timerelease_delay" size="2" value="<?php echo get_option('timerelease_delay')?>"/>
                    <span class="setting-description">
                        <?php _e('Number value greater than 0', 'TimeRelease') ?>
                    </span>

                </label> 
            </td>
        </tr>


        <tr valign="top">
            <th scope="row" class="th-full" colspan="2">
                <label>
                    <input type="checkbox" name="timerelease_post_column" id="timerelease_post_column" value="1"<?php 
                        if (!is_null(get_option('timerelease_post_column', null))) {
                            print ' checked';
                        }
                    ?>/>
                    <?php _e('Show the Time Release column on the posts page', 'TimeRelease') ?>
                </label> 
            </th>
        </tr>


        <tr valign="top">
            <th scope="row" class="th-full" colspan="2">
                <label>
                    <input type="checkbox" name="timerelease_notify" id="timerelease_notify" value="1"<?php 
                        if (!is_null(get_option('timerelease_notify', null))) {
                            print ' checked';
                        }
                    ?>/>
                    <?php _e('Email the post author when publishing a Time Release post', 'TimeRelease') ?>
                </label> 
            </th>
        </tr>

    </table>
    <div class="submit"><input type="submit" name="timerelease_update_options" value="<?php _e('Update Options', 'TimeRelease') ?> &raquo;" /></div>
</form>
</div>
<?php

if ($_GET["timerelease_updated"] == "true"):
?>
    <div class="updated"><p><strong><?php _e('Settings updated.', 'TimeRelease') ?></strong></p></div>
<?php
endif;
?>
