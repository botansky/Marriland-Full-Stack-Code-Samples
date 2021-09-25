<?php
namespace Marriland\IV_Calculator;

use Marriland\Pokemon\Common;
use Marriland\Pokemon\Pokemon as Pokemon; //# using a Pokemon object here
use Marriland\Utility as Utility;
use Twig\Environment as Twig_Environment;
use Marriland\SEO;
use MongoDB\Database as Database;

use function DI\add;

class Main {
	use \Marriland\Pokemon\TGeneration,
		\Marriland\Pokemon\TLanguage;
    //# ^^ uncomment the above if generation and/or language support is
    //# needed! These are PHP traits.
    
	protected $db;
	protected $twig;

    protected $query_vars;

    /** @var \Marriland\Pokemon\Pokemon */
    protected $pokemon;
    protected $calculator;

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
	public function use_db(Database $db): self {
		$this->db = $db;
		return $this;
	}

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
        add_filter('the_content', [$this, 'content'], 0);
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
        wp_enqueue_style('iv-calculator', plugins_url('css/iv-calculator.css', __DIR__), ['main'], null);
        
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

        //# For the record, the identifier for STYLES and SCRIPTS can be the
        //# same with no issue. They don't have to be the same, and they don't
        //# have to be unique.
	    wp_enqueue_script('iv-calculator', plugins_url('js/iv-calculator.js', __DIR__), null, null, true);

        //# wp_localize_script creates a JavaScript variable (`wp` by default)
        //# that contains everything from the array in here. Generally this will
        //# be `ajax_url` and `plugin_url`, but anything else can be piped to
        //# JavaScript from here as well and accessed in JavaScript through
        //# `wp.ajax_url` for instance.
		wp_localize_script('iv-calculator', 'wp', [
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
        $inputs = $_GET;

        /* D: Adding validation code START */
        // First, handle all of the input. There's no point continuing if
        // it isn't valid.

        if (!isset($inputs['name'])) {
            // Don't use an actual message, just a slug which will be
            // referenced client side to avoid potential injection attacks.
            die(json_encode(['error' => "missing-input"]));            
        }

        if(is_array($inputs['levels'])){
            foreach($inputs['levels'] as $level){
                // All of these need to be set or else the tool won't work.
                // These still require further validation, but there's no point
                // even proceeding if these aren't set.
                if (!isset($level['level'])
                || !isset($level['stats']['hp'])
                || !isset($level['stats']['attack'])
                || !isset($level['stats']['defense'])
                || !isset($level['stats']['spatk'])
                || !isset($level['stats']['spdef'])
                || !isset($level['stats']['speed'])
                ) {
                    // Don't use an actual message, just a slug which will be
                    // referenced client side to avoid potential injection attacks.
                    die(json_encode(['error' => "missing-input"]));            
                }
            }
        }
        else{
            die(json_encode(['error' => "missing-input"]));            
        }



        // Remove some potentially harmful characters
        $name = (string) str_replace(['<', '>', '$'], '', $inputs['name']);

        $generation = (int) ($inputs['generation'] ?? Common::$current_generation);
        $language = (string) ($inputs['language'] ?? 'en');

        $nature = null;
        if (isset($inputs['nature']) && $inputs['nature'] !== "none") { // If it's set, remove padding + lowercase it
            $nature = trim(strtolower((string) $inputs['nature']));
        }

        $characteristic = null;
        if (isset($inputs['characteristic']) && $inputs['characteristic'] !== 'none') { // If it's set, remove padding + lowercase it
            $characteristic = trim(strtolower((string) $inputs['characteristic']));

            //Should validate the input based on the pattern of
            ///[a-z]+\-[0-9]{1}\-[0-9]{1}/ so explode() won't break if
            //there are no -'s
            if(isset($inputs['characteristic']) && preg_match('/[a-z]+\-[0-9]{1}\-[0-9]{1}/', $inputs['characteristic']) === false){
                // Don't use an actual message, just a slug which will be
                // referenced client side to avoid potential injection attacks.
                die(json_encode(['error' => "missing-input"]));            
            }
        }

        $hidden_power = null;
        if (isset($inputs['hidden_power']) && $inputs['hidden_power'] !== 'none') {
            $hidden_power = trim(strtolower((string) $inputs['hidden_power']));
        }

        $this->set_generation($generation);
        $this->set_language($language);
        /* D: Validation code END */

        //! $this->pokemon = (new Pokemon())->use_db($this->db);
        $this->pokemon = (new Pokemon())
            ->use_db($this->db)
            ->set_generation($this->generation)
            ->set_language($this->language);
        
        //! $this->calculator = (new IV_Calculator());
        $this->calculator = new IV_Calculator();

        //! $query = ['name' => ucfirst($_GET['name'])];
        //! $result = $this->db->{'pokemon'}->findOne($query);
        //! $this->pokemon->load_by_data($result);
        try {
            $this->pokemon->load_by_name(trim($name));
          } catch (\Exception $e) {
            die(json_encode(['error' => 'invalid-pokemon']));
        }

        //! //# No longer necessary
        //! $this->pokemon->set_generation($_GET['generation']);

        //!if($_GET['nature'] != "none"){
        //!    $this->calculator->set_nature($_GET['nature']);
        //!}
        if ($nature && in_array($nature, ['hardy', 'lonely', 'brave', 'adamant', 'naughty', 'bold', 'docile', 'relaxed', 'impish', 'lax', 'timid', 'hasty', 'serious', 'jolly', 'naive', 'modest', 'mild', 'quiet', 'bashful', 'rash', 'calm', 'gentle', 'sassy', 'careful', 'quirky'], true)) {
            $this->calculator->set_nature($nature);
        }

        //!if($_GET['characteristic'] != "none"){
        //!    $this->calculator->set_characteristic($_GET['characteristic']);
        //!}
        if ($characteristic /* && preg_match(...) // check for valid pattern as well */) {
            $this->calculator->set_characteristic($characteristic);
        }

        //!echo $_GET['hidden_power']; //! This shouldn't be here
        //!if($_GET['hidden_power'] != "none"){
        //!    $this->calculator->set_hidden_power($_GET['hidden_power']);
        //!}
        if ($hidden_power && in_array($hidden_power, ['fighting', 'flying', 'poison', 'ground', 'rock', 'bug', 'ghost', 'steel', 'fire', 'water', 'grass', 'electric', 'psychic', 'ice', 'dragon', 'dark'])) {
            $this->calculator->set_hidden_power($hidden_power);
        }

        if($this->pokemon->get_base_stats() != null){
            $this->calculator->set_raw_base_stats($this->pokemon->get_base_stats());
        }
        else{
            die(json_encode(['error' => 'invalid-pokemon']));        
        }

        // $hp = $_GET['hp'];
        // $attack = $_GET['attack'];
        // $defense = $_GET['defense'];
        // $spatk = $_GET['spatk'];
        // $spdef = $_GET['spdef'];
        // $speed = $_GET['speed'];

        // $hp_ev = $_GET['hp_ev'];
        // $attack_ev = $_GET['attack_ev'];
        // $defense_ev = $_GET['defense_ev'];
        // $spatk_ev = $_GET['spatk_ev'];
        // $spdef_ev = $_GET['spdef_ev'];
        // $speed_ev = $_GET['speed_ev'];


        //loop for every level entry
        foreach($_GET['levels'] as $lv){
            // These are already confirmed to exist.
            $level = (int) $lv['level'];
            $stats = [
                'hp' => (int) $lv['stats']['hp'],
                'attack' => (int) $lv['stats']['attack'],
                'defense' => (int) $lv['stats']['defense'],
                'spatk' => (int) $lv['stats']['spatk'],
                'spdef' => (int) $lv['stats']['spdef'],
                'speed' => (int) $lv['stats']['speed']
            ];

            $effort_values = null;
            if (isset($lv['evs']['hp'])
                || isset($lv['evs']['attack'])
                || isset($lv['evs']['defense'])
                || isset($lv['evs']['spatk'])
                || isset($lv['evs']['spdef'])
                || isset($lv['evs']['speed'])
            ) {
                // If any EVs are set, we can turn `$effort_values` into an array
                $effort_values = [
                    'hp' => (int) ($lv['evs']['hp'] ?? 0),
                    'attack' => (int) ($lv['evs']['attack'] ?? 0),
                    'defense' => (int) ($lv['evs']['defense'] ?? 0),
                    'spatk' => (int) ($lv['evs']['spatk'] ?? 0),
                    'spdef' => (int) ($lv['evs']['spdef'] ?? 0),
                    'speed' => (int) ($lv['evs']['speed'] ?? 0)
                ];
            }

            //!$this->calculator->set_evs(array('hp' => $_GET['hp_ev'], 'attack' => $_GET['attack_ev'], 'defense' => $_GET['defense_ev'], 'spatk' => $_GET['spatk_ev'], 'spdef' => $_GET['spdef_ev'], 'speed' => $_GET['speed_ev']));
            if ($effort_values) {
                // It's not null, so it's a properly forged array.
                $this->calculator->set_evs($effort_values);
            }
            
            //!$level = $_GET['level'];
            //!$current_stats = array('hp' => $_GET['hp'], 'attack' => $_GET['attack'], 'defense' => $_GET['defense'], 'spatk' => $_GET['spatk'], 'spdef' => $_GET['spatk'], 'speed' => $_GET['speed']);

            //! $result = $this->db->{'pokemon'}->findOne($query);
            //! $this->pokemon->load_by_data($result);


            //!$ivs = $this->calculator->calculate_ivs($current_stats, $level);
            $ivs = $this->calculator->calculate_ivs($stats, $level);
        }



        $output = []; //# you can name this whatever tbh, just set it up as
                      //# an array so it will convert to JSON later.

        $output = [
            'slug' => $this->pokemon->get_slug(),
            'name' => $this->pokemon->get_name(),
            'iv_array' => $ivs,

            'next_level_hp' => $this->calculator->estimate_next_narrowing_level('hp', $ivs, $level),
            'next_level_attack' => $this->calculator->estimate_next_narrowing_level('attack', $ivs, $level),
            'next_level_defense' => $this->calculator->estimate_next_narrowing_level('defense', $ivs, $level),
            'next_level_spatk' => $this->calculator->estimate_next_narrowing_level('spatk', $ivs, $level),
            'next_level_spdef' => $this->calculator->estimate_next_narrowing_level('spdef', $ivs, $level),
            'next_level_speed' => $this->calculator->estimate_next_narrowing_level('speed', $ivs, $level),
        ];
		return json_encode($output);
	}

    public function content(string $content): string {
        
        $twig_params = [];

        $output = $this->twig->render('main.tpl.html', $twig_params);
		return $output;
    }
}