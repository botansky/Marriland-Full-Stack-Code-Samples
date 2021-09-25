<?php
/**
 * Plugin Name: Marriland PLUGIN NAME
 * Plugin URI: https://marriland.com
 * Description: DESCRIPTION OF THE PLUGIN GOES HERE (one line; this is shown on Wordpress)
 * Version: 0.1.2
 * Author: AUTHOR
 * Author URI: https://MARRILAND.COM
 * 
 * ^---- all of the above is used by Wordpress; add NOTHING before it!
 * 
 * THIS PART OF THE COMMENTS UNTIL `defined('ABSPATH')` CAN BE REMOVED! Use it
 * to explain the plugin! This section is NOT shown in Wordpress, just while
 * looking through here.
 * 
 * If any commented out code uses {curly braces}, replace the braces and inner
 * text with something that matches that description (it won't work as-is,
 * obviously).
 * 
 * Any comments with `//#` are just helper notes for the skeleton and can be
 * safely removed (after the skeleton folder has been duplicated!).
 * 
 * Any comments that are just `//` can be uncommented if they are needed, or
 * should be left in place to describe that part of the plugin outline.
 * 
 * A standard Marriland plugin uses the following directory structure:
 * 
 * plugin-folder
 * | |
 * | +-- /css      # All CSS files needed for this, specifically.
 * | +-- /sass     # OPTIONAL. For any SASS/LESS/SCSS precompiled CSS stuff.
 * | +-- /js       # For any JavaScript used by this project.
 * | +-- /images   # Any static images (or other related assets) go here
 * | +-- /includes # This is for includes, CLASSES, etc.
 * | | +-- main.class.php  # Responsible for setting up the plugin, but not for
 * | | |                   # any objects or classes it makes.
 * | | +-- shortcodes.class.php  # OPTIONAL. Not always needed, but if Wordpress
 * | | |                         # shortcodes need to be added, it should be
 * | | |                         # done in here.
 * | +-- /views    # Contains any HTML and includes from Twig (templater).
 * | +-- /vendor   # OPTIONAL. If any extra code or assets are needed for this
 * |               # plugin, it can go here.
 * +-- config.php  # For any CONSTANTS that may differ from dev vs live
 * |               # environments (passwords, folder paths, Wordpress page IDs)
 * +-- {folder-name}.php # This file here. Needs same name as folder.
 * 
 * MAKE SURE YOU DO THIS: Change the filename to `{folder-name}.php`. It should
 * match the folder's name.
 */
//# This makes sure Wordpress is loaded; if it isn't, don't load the file.
defined('ABSPATH') or die('Go away.');

//# `Common` has a lot of Pokémon-specific helper stuff
use Marriland\Pokemon\Common;
//# `SEO` gives some SEO-related functionality.
use Marriland\SEO;
//# `Utility` is has more useful stuff including the `loader`
use Marriland\Utility;

//# It's a good idea to define the plugin path and plugin urls as constants just
//# so it's easy enough to change them out if you need to test stuff. Make sure
//# you find + replace {PLUGIN_NAME} (with the braces) and use all-caps when
//# replacing, because this is used later on in this file.
//#--------
//# Uncomment the below after changing the bits, if these are needed.
// define('MARRILAND_{PLUGIN_NAME}_PLUGIN_PATH', plugin_dir_path(__FILE__));
// define('MARRILAND_{PLUGIN_NAME}_PLUGIN_URL', plugins_url(__FILE__));

//# Run the loader to load `config.php`.
//# Anything else that needs to be included first can be loaded here, although
//# its base is `./`, while the next block solely looks in `./includes/`.
Utility::loader([
	'config.php'
], plugin_dir_path(__FILE__) . '/');

//# Proper PHP autoloading was a pain with Wordpress :( So add anything that
//# needs to be loaded from the `includes/` folder in the array down below.
Utility::loader([
	'main.class.php'
], plugin_dir_path(__FILE__) . 'includes/');

/** */
//# Having a marriland_*_init() function is important for Wordpress
//# compatibility. This function is called during the Wordpress hook (defined
//# immediately after this function) and basically sets it up.
//#
//# Put whatever logic needs to be inserted in here to create the Main object
//# and eventually output the page.
//#
//# For more complicated plugins, like the Pokédex, URL rewriting and parameters
//# can be figured out here. Most tools or smaller plugins won't need these,
//# though, so reference something like the Pokédex plugin for an example of how
//# that works.
function marriland_PLUGIN_NAME_init() {
	//# `$page_ids` stores valid CONSTANTS for numeric page IDs from Wordpress.
	//# These are set in `config.php` because they WILL differ between dev and
	//# live, or even testing servers. Page IDs can be pulled in the URL of the
	//# Edit Page link.
	$page_ids = [
	//# IMPORTANT: Uncomment the below bit after changing the PLUGIN part.
	//	MARRILAND_{PLUGIN}_PAGE_ID
	];
	//# This just checks to see if Wordpress is on one of those page IDs. If it
	//# isn't, it doesn't go any further. This helps save a query to some config
	//# table even if it's a little clunky.
	if (!in_array(get_queried_object_id(), $page_ids)) {
		return;
	}

	//# IMPORTANT: This is the Twig stuff. It's a templating engine. This loads
	//# it up and gives you a `$twig` object you can pass in to your `Main()`
	//# object for this plugin.
	//#
	//# The `$loader` loads the `views` folder of the plugin in Twig. Replace
	//# {PLUGIN_NAME} with the definition up above (should be find + replace'd).

	//# IMPORTANT: Uncomment the line below when the PLUGIN_NAME part has been
	//#   V V V    replaced!

	// $loader = new \Twig\Loader\FilesystemLoader(MARRILAND_{PLUGIN_NAME}_PLUGIN_PATH . 'views');

	$twig = new \Twig\Environment($loader, [
	 	'autoescape' => false //# Other Twig parameters can go in here.
		 //# autoescape is OFF by default, but user input shouldn't be displayed
		 //# in Twig anywhere. If it IS, turning this on would be a better idea.
	]);

	//# Connect to the MongoDB and the `pokedex` DB.
	$db = Utility::connect('pokedex');

	//# Actually initiate the plugin. Replace the variable names and `Plugin`
	//# namespace with whatever this ends up being, and throw in anything else
	//# here that's important.
	//# Chaining in ->use_twig($twig) and ->use_db($db) is recommended here, as
	//# shown below.
	//#
	//# IMPORTANT: Uncomment the lines below and make sure they are ready to go!
	//#   V V V

	// $plugin = (new \Marriland\Plugin\Main())
	// 	->use_twig($twig)
	//	->use_db($db);
	// $plugin->init();
}
//# This is actually the Wordpress hook. First parameter is WHEN it is called,
//# which should usually be `wp_head`. Second is the FUNCTION NAME, which needs
//# to match what is up above, and lastly is the priority, or when within the
//# hook (the `wp_head` bit) it is called in the case of a tie.
add_action('wp_head', 'marriland_PLUGIN_NAME_init', 0);
/** */

function ajax_marriland_PLUGIN_NAME() {
	global $args; // is this necessary? I hope not
	$mode = $args['mode'];
	$query_vars = $args['query_vars'];

	$loader = new \Twig\Loader\FilesystemLoader(MARRILAND_POKEDEX_PLUGIN_PATH . 'views');
	$twig = new \Twig\Environment($loader, array(
		'autoescape' => false
	));

	$game = 'swsh';
	$pokedex = new \Marriland\Pokedex\Main($twig, $game);
	$pokedex->init($mode, $query_vars);
	$json = $pokedex->ajax();
	wp_die($json);
}
add_action('wp_ajax_marriland_pokedex', 'ajax_marriland_pokedex');
add_action('wp_ajax_nopriv_marriland_pokedex', 'ajax_marriland_pokedex');