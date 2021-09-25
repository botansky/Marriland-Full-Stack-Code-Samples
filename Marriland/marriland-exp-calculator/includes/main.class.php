<?php
namespace Marriland\EXP_Calculator;

use Marriland\Pokemon\Common;
use Marriland\Utility as Utility;
use Marriland\Pokemon\Pokemon as Pokemon; //# using a Pokemon object here
use Twig\Environment as Twig_Environment;
use Marriland\SEO;
use MongoDB\Database as Database; //# If databases are needed

class Main {
	use \Marriland\Pokemon\TGeneration,
		\Marriland\Pokemon\TLanguage;
    //# ^^ uncomment the above if generation and/or language support is
    //# needed! These are PHP traits.
    
	protected $db;
	protected $twig;

    protected $query_vars;

    public function __construct(?array $query_vars = null) {
        //# If TGeneration is used, this sets up the current generation.
        //# No need for this if generation isn't used in the plugin.
		$this->generation = Common::$current_generation;

        //# If `$query_vars` are passed (for Wordpress/PHP URL params), then
        //# populate $this->query_vars for later, for things like `ajax()`.
        //# It's VERY important that user input get filtered, though, for
        //# security purposes! No need to do that *here* though.
        //# If this isn't necessary, remove it and from the function params.
        $this->query_vars = $query_vars ?? null;
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
	public function use_db(Database $db): self {
		$this->db = $db;
		return $this;
	}

    public function init(): void {
        //# The init() method should be run after Wordpress is set up, after
        //# anything else like DB connections or languages are figured out, etc.
        //#
        //# Insert any routing or logic or stuff here.
        add_filter('the_content', [$this, 'content'], 0);


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
        wp_enqueue_style('exp-calculator', plugins_url('css/exp-calculator.css', __DIR__), ['main'], null);
        
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
	    wp_enqueue_script('exp-calculator', plugins_url('js/exp-calculator.js', __DIR__), null, null, true);
        
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

        //# wp_localize_script creates a JavaScript variable (`wp` by default)
        //# that contains everything from the array in here. Generally this will
        //# be `ajax_url` and `plugin_url`, but anything else can be piped to
        //# JavaScript from here as well and accessed in JavaScript through
        //# `wp.ajax_url` for instance.
        wp_localize_script('exp-calculator', 'wp', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => plugins_url('', __DIR__),
            'default_generation' => Common::$current_generation
        ]);
	}

    //# This is an AJAX method that, if set up in the `plugin-name.php` file
    //# using Wordpress's AJAX hooks, will run when an AJAX request is made.
    //# This should take user input from `$this->query_vars` and NEEDS to be
    //# sanitized/validated. It also should output as a JSON.
	public function ajax() {

		header('Content-type: text/json');

        //start by setting generation and langauge
        $generation = (int) ($_GET['generation'] ?? Common::$current_generation);
        $language = (string) ($_GET['language'] ?? 'en');

        //check for valid user pokemon input
        if (!isset($_GET['user_name']) && $generation == 5 && $generation >= 7) {
            // Don't use an actual message, just a slug which will be
            // referenced client side to avoid potential injection attacks.
            die(json_encode(['error' => "missing-user-pokemon"]));            
        }
        elseif (!isset($_GET['user_name']) && $generation != 5 && $generation < 7) {
            // Don't use an actual message, just a slug which will be
            // referenced client side to avoid potential injection attacks.
            $user_name = 'pachirisu'; //! placeholder          
        }
        else{
            $user_name = $_GET['user_name'];
        }

        //check enemy level, if it is given as a range keep the min and max value without requiring the actual level
        //we check the enemy level first as it is required in any calculation and could be used as a placeholder for the user level in some cases
        $enemy_level_range = false;
        if(isset($_GET['enemy_level']) && (int)$_GET['enemy_level'] <= 100 && ($_GET['enemy_range'] === 'false')){
            $enemy_level = (int)$_GET['enemy_level'];
            $enemy_min = $enemy_level;
            $enemy_max = $enemy_level;
        }
        elseif (isset($_GET['enemy_range']) && ($_GET['enemy_range'])  === 'true'){  //there is a range rather than an exact level
            if(isset($_GET['enemy_min']) && isset($_GET['enemy_max']) && (int)$_GET['enemy_min'] <= (int)$_GET['enemy_max']){ //both min and max values must exist and be valid for this option
                $enemy_level_range = true;
                $enemy_min = (int)$_GET['enemy_min'];
                $enemy_max = (int)$_GET['enemy_max'];
            }
            else{
                die(json_encode(['error' => "missing-enemy-level-range"]));
            }
        }
        else{
            die(json_encode(['error' => "missing-enemy-level"]));            
        }


        $no_user_level = false; //the user level is not necessary for non scaled generations
        //check user level
        if(isset($_GET['user_level']) && (int)$_GET['user_level'] <= 100 && (int)$_GET['user_level'] > 0 && ($_GET['user_level_toggle'] === 'true' || $generation == 5 || $generation >6)){
            $user_level = (int)$_GET['user_level'];
        }
        elseif($generation != 5 && $generation < 7 && ($_GET['user_level_toggle'] === 'false')){
            $no_user_level = true;
            $user_level = 1;
        }
        else{
            die(json_encode(['error' => "missing-user-level"]));            
        }

        //set target level
        if(isset($_GET['target_level']) && (int)$_GET['target_level'] <= 100 && (int)$_GET['target_level'] > $user_level){
            $target_level = (int)$_GET['target_level'];
        }
        elseif(isset($_GET['user_level']) && !$no_user_level){
            $target_level = $user_level + 1;
        }
        else{
            if(!$no_user_level){
                die(json_encode(['error' => "missing-user-level"]));
            }
        }
        
        //check for valid enemy pokemon input
        if (!isset($_GET['enemy_name'])) {
            // Don't use an actual message, just a slug which will be
            // referenced client side to avoid potential injection attacks.
            die(json_encode(['error' => "missing-enemy-pokemon"]));            
        }
        else{
            $enemy_name = $_GET['enemy_name'];
        }

        //check all boolean modifiers (except exp. share)
        $traded = false;
        if (isset($_GET['traded']) && ($_GET['traded'])  === 'true'){
            $traded = true;
        }

        $foreign = false;
        if (isset($_GET['foreign']) && ($_GET['foreign'])  === 'true'){
            $foreign = true;
        }

        $lucky_egg = false;
        if (isset($_GET['lucky_egg']) && ($_GET['lucky_egg'])  === 'true'){
            $lucky_egg = true;
        }

        $exceed_evolution = false;
        if (isset($_GET['exceed_evolution']) && ($_GET['exceed_evolution'])  === 'true'){
            $exceed_evolution = true;
        }

        $is_trainer = false;
        if (isset($_GET['trainer']) && ($_GET['trainer'])  === 'true'){
            $is_trainer = true;  
        }

        $exp_charm = false;
        if (isset($_GET['exp_charm']) && ($_GET['exp_charm'])  === 'true'){
            $exp_charm = true;
        }

        //check exp. share and determine if to use the used modifier (when a pokemon holding an exp. share is used in battle)
        $exp_share = false;
        $used = true;
        if (isset($_GET['exp_share']) && ($_GET['exp_share'])  === 'true'){
            $exp_share = true;
            if (isset($_GET['used']) && ($_GET['used'])  === 'false'){
                $used = false;
            }
        }

        //numerical input checks
        $pass_power = 0;
        if (isset($_GET['pass_power']) && abs((int)($_GET['pass_power'])) <= 3){
            $pass_power = (int)$_GET['pass_power'];
        }

        $o_power = 0;
        if(isset($_GET['o_power']) && (int)($_GET['o_power']) <= 3){
            $o_power = (int)($_GET['o_power']);
        }

        $rotom_power = false;
        if (isset($_GET['rotom_power']) && ($_GET['rotom_power'])  === 'true'){
            $rotom_power = true;
        }

        $affection = false;
        if(isset($_GET['affection']) && (int)($_GET['affection']) >= 2){
            $affection = true;
        }

        //set pokemon for calculator constructor
        $user_pokemon = (new Pokemon())
            ->use_db($this->db)
            ->set_generation($generation)
            ->set_language($this->language);
        $enemy_pokemon = (new Pokemon())
            ->use_db($this->db)
            ->set_generation($generation)
            ->set_language($this->language);

        try {
            $user_pokemon->load_by_name(trim($user_name));
          } catch (\Exception $e) {
            die(json_encode(['error' => 'invalid-pokemon']));
        }

        try {
            $enemy_pokemon->load_by_name(trim($enemy_name));
          } catch (\Exception $e) {
            die(json_encode(['error' => 'invalid-pokemon']));
        }

        //initialize calculator for inputs
        $calculator = new EXP_Calculator($user_pokemon, $enemy_pokemon);
        $calculator->set_generation($generation);
        if(!$no_user_level){
            $calculator->set_user_level($user_level);
            $calculator->set_target_level($target_level);
        }
        else{ //throwaway values, not actually used
            $calculator->set_user_level(1);
            $calculator->set_target_level(2);
        }

        //! We will wait until the end to set the enemy level because the calculation may have to be done twice in the case of a ranged
        //! enemy level.

        //set modifiers
        $calculator->set_trainer_battle($is_trainer);
        $calculator->set_traded($traded);
        $calculator->set_foreign($foreign);
        $calculator->set_lucky_egg($lucky_egg);
        $calculator->set_exp_share($exp_share);
        $calculator->set_used_in_battle($used);
        $calculator->set_pass_power($pass_power);
        $calculator->set_o_power($o_power);
        $calculator->set_rotom_power($rotom_power);
        $calculator->set_passed_evolution_level($exceed_evolution);
        $calculator->set_affection_hearts($affection);
        $calculator->set_exp_charm($exp_charm);

        //instantiate output variables
        //min and max variables are only used in the case where we have an enemy level range
        $exp_to_target = 0;
        $enemy_count = 0;
        $min_enemy_count = 0;
        $max_enemy_count = 0;
        $single_win = 0;
        $min_single_win = 0;
        $max_single_win = 0;

        $user_image = $user_pokemon->get_sprite();
        $enemy_image = $enemy_pokemon->get_sprite();

        //now we set the enemy levels and calculate as required
        if($enemy_level_range === false){
            $calculator->set_enemy_level($enemy_level);  
            $exp_to_target = $calculator->get_exp_to_target_level();
            $single_win = $calculator->calculate_exp_yield($user_level);
            if(!$no_user_level){
                $enemy_count = $calculator->calculate_enemies_to_defeat($exp_to_target);
            }
        }
        else{
            $calculator->set_enemy_level($enemy_min);
            $exp_to_target = $calculator->get_exp_to_target_level();


            $min_single_win = $calculator->calculate_exp_yield($user_level);
            if(!$no_user_level){
                $max_enemy_count = $calculator->calculate_enemies_to_defeat($exp_to_target);
            }

            $calculator->set_enemy_level($enemy_max);
            $max_single_win = $calculator->calculate_exp_yield($user_level);
            if(!$no_user_level){
                $min_enemy_count = $calculator->calculate_enemies_to_defeat($exp_to_target);
            }
        }

        $output = [
            'user_image' => $user_image,
            'enemy_image' => $enemy_image,
            'user_name' => $user_pokemon->get_name(),
            'enemy_name' => $enemy_pokemon->get_name(),
            'target_level' => $target_level,
            'exp_to_target' => $exp_to_target,
            'enemy_count' => $enemy_count,
            'min_enemy_count' => $min_enemy_count,
            'max_enemy_count' => $max_enemy_count,
            'single_win' => $single_win,
            'min_single_win' => $min_single_win,
            'max_single_win' => $max_single_win,
            'calculated' => true
        ];  //# you can name this whatever tbh, just set it up as
            //# an array so it will convert to JSON later.
		
        return json_encode($output);
	}

    public function content(string $content): string {
        
        $twig_params = [];

        $output = $this->twig->render('main.tpl.html', $twig_params);
		return $output;
    }
}