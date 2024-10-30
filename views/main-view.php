<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://TheWebDeveloper.io
 * @since      1.0.0
 *
 * @package    Media_File_Organizer
 * @subpackage Media_File_Organizer/admin/partials
 */
?>

<h1><?php _e('Media File Organizer', 'media-file-organizer' ) ?></h1>

<table class="media-file-organizer" id="panels">
    <tbody>
    <tr>
        <td id="left_panel_container" class="panel_container"><?php  echo $left_panel ?></td>
        <td id="buttons_container">
            <div class="move_left"></div>
            <div class="move_right"></div>
        </td>
        <td id="right_panel_container" class="panel_container"><?php  echo $right_panel ?></td>
    </tr>
    </tbody>
    <tfoot>
    <tr>
    <td colspan="3">
        <div id="progress">
            <div id="status">
                Current Status : Idle
            </div>
            <div id="activity" class="scrollable">

            </div>
        </div>
    </td>
    </tr>
    </tfoot>
</table>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
