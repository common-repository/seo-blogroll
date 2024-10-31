<?php
/*
Plugin Name: SEO Blogroll
Plugin URI: http://www.francesco-castaldo.com/plugins-and-widgets/seo-blogroll/
Description: Lets you decide which blog roll links have the nofollow attribute. Don't waste link juice!
Author: Francesco Castaldo
Version: 1.0.2
Author URI: http://www.francesco-castaldo.com/

Installing
1. Unzip and upload 'seo-blogroll' directory to your '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in the Admin panel
3. Edit your links in the plugin options page under "Settings" -> "SEO Blogroll". Create how many boxes you like, each with its own title and link list.
4. Disable the traditional "Links" widget in "Appearace" -> "Widgets"
5. Enable SEO Blogroll widget under "Appearance" -> "Widgets"

Changelog
1.0.2 = installation checks requirements
1.0.1 = xhtml fix.
1.0 = First public release.
*/

/*
Copyright 2009  Francesco Castaldo  (email : fcastaldo@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $seoBlogRollPhpVersion;
$seoBlogRollPhpVersion = phpversion();
if (strpos($seoBlogRollPhpVersion, '-') !== false) {
	$seoBlogRollPhpVersion = substr($seoBlogRollPhpVersion, 0, strpos(phpversion(), '-'));
}
$seoBlogRollPhpVersion = floatval($seoBlogRollPhpVersion);

if ($seoBlogRollPhpVersion >= 5) {
	// causes error in php 4
	include('seo-blogroll-dataobjects.php');
}



class SEO_BG_plugin {

	public function get_option_page() {


		//delete_option('seo-blogroll');

		// first time a new box is stored there's an error


		$message = false;

		$link_boxes = get_option('seo-blogroll');

		//$link_boxes = false;

		// if nothing stores, create a new box with default values
		if ($link_boxes == false) {
			$link_boxes = new SEO_LinkBoxes();
			
			$seo_blogroll = new SEO_Blogroll();
			$seo_blogroll->setDefaults();
			$link_boxes->addBlogroll($seo_blogroll);
		} else {
			if (isSet($_GET["delete"]) && strLen($_GET["delete"]) > 0 && is_numeric($_GET["delete"])) {
				$link_boxes->removeBlogroll($_GET["delete"]);
				update_option('seo-blogroll', $link_boxes);
				header("Location: options-general.php?page=seo-blogroll.php");
			}
		}
		// the stored object does not have empty fields, I need to add them
		$link_boxes->addEmptyLinks();

		if (isSet($_POST["action"])) {
			// if the user is sending one more linkbox, the stored version does not have it and fields won't be processed
			// i need to add and empty one, just in case... and then remember to clean
			$link_boxes->addBlogroll(new SEO_Blogroll());

			$i = 1;
			$oldBlogRolls = $link_boxes->resetBlogrolls();
			foreach ($oldBlogRolls as $seo_blogroll) {
				if (isSet($_POST["seo-bg-title-".$i])) {
					$seo_blogroll->setTitle($_POST["seo-bg-title-".$i]);
					$seo_blogroll->setOrder($_POST["seo-bg-order-".$i]);
					$links = $seo_blogroll->resetLinks();
					if ($links != null) {
						foreach ($links as $link) {
							if (strToLower(substr($_POST["seo-bg-link-url-".$link->getId()."-".$i], 0, 7)) == "http://") {
								$newLink = new SEO_BR_Link($_POST["seo-bg-link-url-".$link->getId()."-".$i], $_POST["seo-bg-link-text-".$link->getId()."-".$i], $_POST["seo-bg-link-nofollow-".$link->getId()."-".$i], $_POST["seo-bg-link-target-".$link->getId()."-".$i]);
								$seo_blogroll->addLink($newLink);
							}
						}
					}
					$seo_blogroll->cleanEmptyLinks();
					$link_boxes->addBlogroll($seo_blogroll);
				}
				$i++;
			}

			//sort
			$link_boxes->sortBoxes();
			
			$test = get_option('seo-blogroll');
			if ($test == false) {
				// is there a way to know if storing options went ok? docs say nothing
				add_option('seo-blogroll', $link_boxes, ' ', 'no');
				$message = true;
			} else {
				// is there a way to know if storing options went ok? docs say nothing
				update_option('seo-blogroll', $link_boxes);
				$message = true;
			}

			// add a new box
			if (isSet($_POST["add"])) {

				$seo_blogroll = new SEO_Blogroll();
				$seo_blogroll->setOrder($link_boxes->howManyBoxes());
				//$seo_blogroll->setDefaults();

				if (isSet($_POST["seo-bg-new-linkbox-title"]) && strlen($_POST["seo-bg-new-linkbox-title"]) > 0) {
					$seo_blogroll->setTitle($_POST["seo-bg-new-linkbox-title"]);
				}
				$link_boxes->addBlogroll($seo_blogroll);
			}

			// after storing I need to add empty fields at the end,
			//because I cleaned up everything before storing
			$link_boxes->addEmptyLinks();
		}
?>
<script text="text/javascript">
	function deleteBox(id) {
		window.location.href = window.location.href + "&delete=" + id;
	}
</script>
<div class="wrap">
	<h2>SEO Blogroll</h2>
	<?php if ($message) { ?>
	<div id="message" class="updated fade"><p>Links updated. Continue editing below or <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2854417">donate for this plugin</a>.</p></div>
	<? } ?>

	<form method="post">
	<?php wp_nonce_field('update-options'); ?>
		<div id="poststuff" class="metabox-holder">

		<div class="postbox">
			<h3 class="hndle"><span>What is this</span></h3>
			<div class="inside">
				<p><?php _e('This plugin lets you decide, for each Blogroll link, if it should have the the <em>rel="nofollow"</em> attribute or not.'); ?></p>
				<p><?php _e('Without the <em>rel="nofollow"</em> attribute, the link is passing its <a href="http://www.google.com/search?q=define:link+juice" rel="nofollow" target="_blank">juice</a> to the destination page. It\'s better if you carefully choose who merits some of your PageRank and who doesn\'t.'); ?></p>
			</div>
		</div>	
		<div class="postbox">
			<h3 class="hndle"><span>Instructions</span></h3>
			<div class="inside">
				<p><?php _e('If you need to add more links, just save changes. The page will reload and show more empty links.'); ?><br />
				<?php _e('Links in URL field must begin with <strong>"http://"</strong>. If not, they are not saved.'); ?><br />
				<?php _e('To remove a link, just delete the "Url" field.'); ?><br />
				<?php _e('Make sure you activated the "SEO Blogroll" widget in your sidebar, otherwise your blog won\'t display any link.'); ?><br />
				<?php _e('Make also sure the default "Link" widget is not acrivated, unless you want 2 blogrolls and wasting link juice.'); ?></p>
			</div>
		</div>
		
		<?php $i = 1; ?>
		<?php foreach($link_boxes->getBlogrolls() as $seo_blogroll) { ?>
			<div class="postbox">
				<h3 class="hndle"><span><?php echo ($seo_blogroll->getTitle()); ?></span> (<a href="javascript:deleteBox(<?php echo $seo_blogroll->getOrder(); ?>);"><?php _e('delete'); ?></a>)</h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<td><label for="seo-bg-title-<?php echo($i); ?>"><?php _e('Box title:'); ?></label></td>
							<td><input style="width: 250px;" id="seo-bg-title-<?php echo($i); ?>" name="seo-bg-title-<?php echo($i); ?>" type="text" value="<?php echo $seo_blogroll->getTitle(); ?>" /></td>
							<td><label for="seo-bg-order-<?php echo($i); ?>"><?php _e('Order:'); ?></label></td>
							<td><input style="width: 40px;" id="seo-bg-order-<?php echo($i); ?>" name="seo-bg-order-<?php echo($i); ?>" type="text" value="<?php echo $seo_blogroll->getOrder(); ?>" /></td>
						</tr>
						<?php foreach ($seo_blogroll->getLinks() as $link) { ?>
							<tr valign="top">
								<td colspan="4">
									<table style="border:1px solid #999999;margin:0;padding:7px;">
										<tr>
											<td><label for="seo-bg-link-text-<?php echo($link->getId()); ?>-<?php echo($i); ?>"><?php _e('Link text:'); ?></label></td>
											<td><input style="width:260px;" id="seo-bg-link-text-<?php echo($link->getId()); ?>-<?php echo($i); ?>" name="seo-bg-link-text-<?php echo($link->getId()); ?>-<?php echo($i); ?>" type="text" value="<?php echo($link->getText()); ?>" /></td>
											<td><label for="seo-bg-link-url-<?php echo($link->getId()); ?>-<?php echo($i); ?>"><?php _e('Link url:'); ?></label></td>
											<td><input style="width:260px;" id="seo-bg-link-url-<?php echo($link->getId()); ?>-<?php echo($i); ?>" name="seo-bg-link-url-<?php echo($link->getId()); ?>-<?php echo($i); ?>" type="text" value="<?php echo($link->getUrl()); ?>" /></td>
										</tr>
										<tr>
											<td><label for="seo-bg-link-target-<?php echo($link->getId()); ?>"><?php _e('Link target:'); ?></label></td>
											<td>
												<select style="width:260px;" id="seo-bg-link-target-<?php echo($link->getId()); ?>-<?php echo($i); ?>" name="seo-bg-link-target-<?php echo($link->getId()); ?>-<?php echo($i); ?>">
													<option value=""<?php if ($link->getTarget() == null) { echo(" selected=\"selected\""); } ?>>self (open link in the same window)</option>
													<option value="_blank"<?php if ($link->getTarget() == "_blank") { echo(" selected=\"selected\""); } ?>>_blank (open link in a new window)</option>
												</select>
											</td>
											<td style="text-align:right"><input id="seo-bg-link-nofollow-<?php echo($link->getId()); ?>-<?php echo($i); ?>" name="seo-bg-link-nofollow-<?php echo($link->getId()); ?>-<?php echo($i); ?>" type="checkbox" value="true" <?php if ($link->isNoFollow() == true) { echo "checked=\"checked\""; } ?>" /></td>
											<td><label for="seo-bg-link-nofollow-<?php echo($link->getId()); ?>-<?php echo($i); ?>"><?php _e('Add nofollow parameter'); ?></label></td>
										</tr>
									</table>
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
			</div>
			<?php $i++; ?>
		<?php } ?>
		<div class="postbox">
			<h3 class="hndle"><span>Add one more link box</span></h3>
			<div class="inside">
				<p><?php _e('Split links into categories: add more link boxes to your blog!'); ?></p>
				<p><label for="seo-bg-new-linkbox-title"><?php _e('Box title:'); ?> <input style="width: 350px;" id="seo-bg-new-linkbox-title" name="seo-bg-new-linkbox-title" type="text" /></label>
				<p class="submit"><input type="submit" name="add" value="<?php _e('Add') ?>" /></p>
			</div>
		</div>
		<input type="hidden" name="action" value="update" />

		<p class="submit"><input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" /></p>

		</div>
	</form>
</div>

<?php
	}
}

class SEO_BG_widget {

	/**
	 * Widget box
	 */
	public function get_widget_box($args) {

		//global $user_level;
		extract($args);

		$link_boxes = get_option('seo-blogroll');

		$blogrolls = $link_boxes->getBlogrolls();
		if ($blogrolls != null)
		{
			echo $before_widget;
			foreach($blogrolls as $seo_blogroll) {
				if ($seo_blogroll->getLinks() != null) {
					echo $before_title . $seo_blogroll->getTitle() . $after_title;
	?>
					<div>
						<ul class="xoxo blogroll">
						<?php foreach ($seo_blogroll->getLinks() as $link) { ?>
							<li><a href="<?php echo($link->getUrl()); ?>"<?php if ($link->isNoFollow()) { echo(" rel=\"nofollow\""); } ?><?php if ($link->getTarget() == "_blank") { echo(" target=\"_blank\""); } ?>><?php echo($link->getText()); ?></a></li>
						<?php } ?>
						</ul>
					</div>
	<?php
				}
			}
			echo $after_widget;
		}
	}

	/**
	 * Widget control panel
	 */
	public function get_widget_control() {
	
?>
		<p>Save changes and you'll be done. See <a href="options-general.php?page=seo-blogroll.php">SEO Blogroll options page</a> to manage your links.<br />Be careful with link juice!! ;-)</p>
<?php
	}
}


// init widget
function SEO_BG_widget_init() {
	// hopefully this won't cause errors if the wordpress version does not support widgets
	if (function_exists('register_widget_control')) {
		register_widget_control('SEO Blogroll', array('SEO_BG_widget', 'get_widget_control'));
	}
	if (function_exists('register_sidebar_widget')) {
		register_sidebar_widget('SEO Blogroll', array('SEO_BG_widget', 'get_widget_box'));
	}
}

// init plugin
function add_SEO_BG_option_page() {
	if (function_exists('add_options_page')) {
		add_options_page('SEO Blogroll', 'SEO Blogroll', 9, basename(__FILE__), array("SEO_BG_plugin", "get_option_page"));
	}
}

// init everything
if (function_exists("add_action") && function_exists("register_activation_hook")) {
	register_activation_hook( __FILE__, "SEQ_BG_act");
	if ($seoBlogRollPhpVersion > 5) {
		add_action("init", "SEO_BG_widget_init");
		add_action('admin_menu', 'add_SEO_BG_option_page');
	}
}

function SEQ_BG_act() {
	global $wp_version, $seoBlogRollPhpVersion;

	if (version_compare($wp_version, '2.5.0', '<')) {
		$message = '<p>This plugin requires WordPress 2.5 or higher, which you do not have. Please upgrade your wordpress core.<p>';
		if (function_exists('deactivate_plugins')) {
			deactivate_plugins(__FILE__);
			$message .= "<p>The plugin has been disabled.</p>";
		} else {
			$message .= '<p><strong>Please deactivate this plugin Immediately</strong></p>';
		}
		die($message);
	} else {
		// wordpress version is ok, let's check php
		if ($seoBlogRollPhpVersion < 5) {
			$message = '<p>This plugin requires Php5 or higher, your current php version is '.phpversion().'</p>';
			deactivate_plugins(__FILE__);
			$message .=  "<p>The plugin has already been deactivated and it won't mess your blog up.</p>";
			die($message); // better way of doing this?
		}
	}
}
?>