=== Media File Organizer ===
Contributors: TheWebDeveloper
Tags: media,file,manager,explorer,relocate,folder,folders,files,rename,make directory,directories,organize,organize,organizer,organiser
Requires at least: 4.3.0
Tested up to: 5.1.0
Stable tag: 1.0.1
Donate link: http://TheWebDeveloper.io
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Organize files and folders inside the media library directory by moving them to sub directories or renaming them while also updating all references to them in pages and posts.

== Description ==

This plugin relocates (moves or renames) files and folders inside the media library directory, and performs any  necessary database queries to update attachment posts and all references to them in pages and posts

This plugin is a rewrite of both Media Organiser plugin by Chris Dennis and the original media file manager plugin by Atsushi Ueda

**Please make sure to backup your files before using the plugin as it makes changes to the file system and database.**

== Requirements ==

* MySQL database engine that does transactions, otherwise the plugin will not be able to undo any changes made to the posts or posts meta table if the relocation process was interrupted for any reason.


== Acknowledgements ==

Some Icons adapted from [github.com/iconic/open-iconic/](https://github.com/iconic/open-iconic).

== Known issues ==

* May not work on sites hosted on a Windows server

* Increases php execution time as this is needed for some operations that involve moving large files or many files, if your hosting provider doesn't allow that to be done from code then it will be an issue.

== Installation ==

Download the plugin and upload it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.0.1 =
* Fixed batch move bug
* Fixed some UI issues 

= 1.0.0 =
* Initial Version
