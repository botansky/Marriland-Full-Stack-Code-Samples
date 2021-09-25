<?php
namespace Marriland\PLUGIN_NAME;

use Marriland\Pokemon\Common;
use Marriland\Utility as Utility;
use Twig\Environment as Twig_Environment;
use Marriland\SEO;
// use MongoDB\Database as Database; //# If databases are needed

class Main {
	// use \Marriland\Pokemon\TGeneration,
	//	\Marriland\Pokemon\TLanguage;
    //# ^^ uncomment the above if generation and/or language support is
    //# needed! These are PHP traits.
    
	protected $db;
	protected $twig;

    protected $query_vars;

    public function __construct(?array $query_vars = null) {
        //# If TGeneration is used, this sets up the current generation.
        //# No need for this if generation isn't used in the plugin.
		// $this->generation = Common::$current_generation;

        //# If `$query_vars` are passed (for Wordpress/PHP URL params), then
        //# populate $this->query_vars for later, for things like `ajax()`.
        //# It's VERY important that user input get filtered, though, for
        //# security purposes! No need to do that *here* though.
        //# If this isn't necessary, remove it and from the function params.
        // $this->query_vars = $query_vars ?? null;
        return $this;
    }

	/**
	 * Enables the Twig templating engine for this Pokédex instance.
	 * 
	 * Previously, this was set during `__construct()`, but there were a LOT of
	 * instances I didn't need the Twig engine and it was just really clunky.
	 * 
	 * Now, if I need to use Twig, this ensures that it is loaded up.
	 * 
	 * @param \Twig\Environment $twig Initialized Twig environment.
	 * @return self Chainable.
	 */
	public function use_twig(Twig_Environment $twig): self {
		$this->twig = $twig;
		return $this;
	}

	/**
	 * Enables the MongoDB database for this Pokédex instance.
	 */
    //# Uncomment the method below if the database is needed for this plugin.
    //# The connection to the DB should be established in plugin-name.php, not
    //# in this Class. Just inject it here if it's needed.
    /*
	public function use_db(Database $db): self {
		$this->db = $db;
		return $this;
	}
    */

    public function init(): void {
        //# The init() method should be run after Wordpress is set up, after
        //# anything else like DB connections or languages are figured out, etc.
        //#
        //# Insert any routing or logic or stuff here.


        //# After all of that, add some Wordpress actions to register both CSS
        //# and JavaScript files. The [array] is used to signal a call to $this
        //# object's `'styles'` or `'scripts'` (or whatever index 1 is) method.

		// Time to pull the styles.
		add_action('wp_enqueue_scripts', [$this, 'styles']);

		// Next pull the JavaScript. Shouldn't be that much involved.
		add_action('wp_enqueue_scripts', [$this, 'scripts']);

        //# You can also run the Wordpress page through a filter so all of its
        //# content ends up in a parameter and you can modify it. This is more
        //# useful for things like the Pokédex and less so with tools.
        //# If this is used, just make sure the appropriate method is added.
        //# (It will be commented out further below.)
    }

	/**
	 * Styles across the entirety of the plugin
	 * 
	 * Run as part of a Wordpress hook during the process of readying styles.
	 * 
	 * @return void
	 * @see `Main::init()` for where this hook and other hooks are run.
	 */
	public function styles(): void {
        //# The `wp_enqueue_style()` Wordpress function registers and queues a
        //# CSS style.

        //# Explanation of what this is can be found down below.
        //# IMPORTANT: Uncomment below when it's been fixed.
        //#  V V V
        // wp_enqueue_style('IDENTIFIER', plugins_url('css/FILENAME.css', __DIR__), ['main'], null);
        
        /* //# This is just for reference for what this is:
		wp_enqueue_style(
            
            'IDENTIFIER',       # Identifying 'slug' for this style (used for
                                # CSS/JS dependencies.)

            plugins_url('css/FILENAME.css', __DIR__), # This pulls the CSS from
                                            # the plugin root folder with this
                                            # set up. It's a little weird if you
                                            # want to go outside of the plugin
                                            # folder though thanks to Wordpress.
                                    # NOTE: You can also just use a URL for
                                    # remote/CDN CSS.

            ['main'],           # Array of dependencies (using the 'slug' from
                                # the first parameter), or `null` for none.

            null                # Version for caching purposes, or `null` for NO
                                # version. Leaving this blank/unset is BAD
                                # because it exposes the Wordpress version.
        );
        */
	}

	/**
	 * JavaScript scripts across the entirety of the plugin
	 * 
	 * Run as part of a Wordpress hook during the process of readying scripts.
	 * 
	 * @return void
	 * @see `Main::init()` for where this hook and other hooks are run.
	 */
	public function scripts(): void {
        //# The `wp_enqueue_script()` Wordpress function registers and queues a
        //# JavaScript file.

        //# Explanation of what this is can be found down below.
        //# IMPORTANT: Uncomment below when it's been fixed.
        //#  V V V
	    // wp_enqueue_script('IDENTIFIER', plugins_url('js/FILENAME.js', __DIR__), null, null, true);
        
        /* //# This is just for reference for what this is:
		wp_enqueue_script(
            
            'IDENTIFIER',       # Identifying 'slug' for this style (used for
                                # CSS/JS dependencies.)

            plugins_url('js/FILENAME.js', __DIR__), # This pulls the JS from
                                            # the plugin root folder with this
                                            # set up. It's a little weird if you
                                            # want to go outside of the plugin
                                            # folder though thanks to Wordpress.
                                    # NOTE: You can also just use a URL for
                                    # remote/CDN JS.

            ['main'],           # Array of dependencies (using the 'slug' from
                                # the first parameter), or `null` for none.

            null,               # Version for caching purposes, or `null` for NO
                                # version. Leaving this blank/unset is BAD
                                # because it exposes the Wordpress version.
            true                # Lastly this is TRUE if in the footer;
                                # otherwise it goes in the <head>.
        );
        */
	}

    //# This is an AJAX method that, if set up in the `plugin-name.php` file
    //# using Wordpress's AJAX hooks, will run when an AJAX request is made.
    //# This should take user input from `$this->query_vars` and NEEDS to be
    //# sanitized/validated. It also should output as a JSON.
	public function ajax() {
		header('Content-type: text/json');

        $output = []; //# you can name this whatever tbh, just set it up as
                      //# an array so it will convert to JSON later.
		return json_encode($output);
	}
}