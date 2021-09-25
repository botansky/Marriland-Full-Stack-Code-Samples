<?php
namespace Marriland\IV_Calculator;

use Marriland\Pokemon\Common;
use Marriland\Pokemon\Pokemon as Pokemon; //# using a Pokemon object here
use Marriland\Utility as Utility;
use Twig\Environment as Twig_Environment;
use Marriland\SEO;
use MongoDB\Database as Database;

use function DI\string;

class IV_Calculator {
    
    protected $possible_ivs = array('hp' => null, 'attack' => null, 'defense' => null, 'spatk' => null, 'spdef' => null, 'speed' => null); //output array, will be a nested array
    protected $heighest_iv = 31; //max possible iv value, can change with characteristics
    protected $heighest_iv_index; //stat index marked by characteristic (if any)
    protected $base_stats = array('hp' => 0, 'attack' => 0, 'defense' => 0, 'spatk' => 0, 'spdef' => 0, 'speed' => 0);

    protected $initial_level_calculated = false; //determines if at least one level has already been calculated, used for level tiering

    //input field protects
    protected $raw_base_stats = [];
    protected $evs = array('hp' => 0, 'attack' => 0, 'defense' => 0, 'spatk' => 0, 'spdef' => 0, 'speed' => 0);
    protected $nature = 'docile'; //default to a neutral nature
    protected $characteristic = null;
    protected $hidden_power_type = null;

    protected $errors = []; //array to return in case of errors

    protected const NATURE_EFFECTS = [
        'hardy' => ['up' => false, 'down' => false],
        'lonely' => ['up' => 'attack', 'down' => 'defense'],
        'brave' => ['up' => 'attack', 'down' => 'speed'],
        'adamant' => ['up' => 'attack', 'down' => 'spatk'],
        'naughty' => ['up' => 'attack', 'down' => 'spdef'],
        'bold' => ['up' => 'defense', 'down' => 'attack'],
        'docile' => ['up' => false, 'down' => false],
        'relaxed' => ['up' => 'defense', 'down' => 'speed'],
        'impish' => ['up' => 'defense', 'down' => 'spatk'],
        'lax' => ['up' => 'defense', 'down' => 'spdef'],
        'timid' => ['up' => 'speed', 'down' => 'attack'],
        'hasty' => ['up' => 'speed', 'down' => 'defense'],
        'serious' => ['up' => false, 'down' => false],
        'jolly' => ['up' => 'speed', 'down' => 'spatk'],
        'naive' => ['up' => 'speed', 'down' => 'spdef'],
        'modest' => ['up' => 'spatk', 'down' => 'attack'],
        'mild' => ['up' => 'spatk', 'down' => 'defense'],
        'quiet' => ['up' => 'spatk', 'down' => 'speed'],
        'bashful' => ['up' => false, 'down' => false],
        'rash' => ['up' => 'spatk', 'down' => 'spdef'],
        'calm' => ['up' => 'spdef', 'down' => 'attack'],
        'gentle' => ['up' => 'spdef', 'down' => 'defense'],
        'sassy' => ['up' => 'spdef', 'down' => 'speed'],
        'careful' => ['up' => 'spdef', 'down' => 'spatk'],
        'quirky' => ['up' => false, 'down' => false]
    ];

    protected const STAT_INDEXES = ['hp', 'attack', 'defense', 'spatk', 'spdef', 'speed'];

    public function __construct()
    {
        return $this;
    }

    // sets raw base stats determined by output from main pokemon class
    // input: raw base stat array
    public function set_raw_base_stats(array $base_stats){
        $this->base_stats = $base_stats;
    }

    //sets evs, must be updated
    //input: ev array
    public function set_evs(array $evs){
        $this->evs = $evs;
    }

    //returns evs
    //output: ev array
    public function get_evs(){
        return $this->evs;
    }

    //sets nature, must be updated
    //input: nature string
    public function set_nature(string $nature){
        $this->nature = $nature;
    }

    //returns nature
    //output: nature string 
    public function get_nature(){
        return $this->nature;
    }

    //sets characteristic, must be updated
    //input: characteristic ('name-x-y')
    public function set_characteristic(string $characteristic){
        //use explode and put characteristics into an array
        list($stat_index, $mod_offset, $mod_offset_plus) = explode('-', $characteristic);
        $this->characteristic = array($stat_index, $mod_offset);
    }

    //returns characteristic
    //output: characteristic string of index format [0] -> stat index, [1] -> modulus offset
    public function get_characteristic(){
        return $this->characteristic;
    }

    //sets hidden power type, must be updated
    //input: type as string
    public function set_hidden_power(string $hidden_power_type){
        $this->hidden_power_type = $hidden_power_type;
    }

    //returns hidden power type
    //output: type as string
    public function get_hidden_power(){
        return $this->hidden_power_type;
    }

    //sets iv array to possible iv nested array
    //input: possible iv's for the specific stat, the stat index (hp = 0, attack = 1, etc.)
    protected function slot_possible_ivs(array $possible_for_stat, string $stat_index){

        if($this->possible_ivs[(string)$stat_index] == null){
            $this->possible_ivs[$stat_index] = $possible_for_stat;
        }
    }


    //picks the correct calculation function and produces the result
    //inputs: current stat values, current level
    //output: iv array
    public function calculate_ivs(array $stat_values, int $lvl){

        if(!$this->initial_level_calculated){
            $this->calculate_inital_ivs($stat_values, $lvl);
        } else {
            $this->calculate_new_level_ivs($this->possible_ivs, $stat_values, $lvl);
        }

        foreach(self::STAT_INDEXES as $stat_index) {
            if ($this->possible_ivs[$stat_index] == null) {
                $this->possible_ivs[$stat_index] = [];
            }
        }

        if($this->hidden_power_type != null){
            $hpow = new Hidden_Power_IVs($this->possible_ivs, $this->hidden_power_type);
            $filtered = $hpow->get_hidden_power_filtered_ivs();
            foreach(self::STAT_INDEXES as $stat_index){
                $filtered[$stat_index] = array_values($filtered[$stat_index]);
            }
            $this->possible_ivs = $filtered;
        }

        return $this->possible_ivs;
    }

    //calculates all ivs using supporting functions - THIS IS THE MAIN FUNCTION FOR THE INITIAL ITERATION
    //inputs: current stat values, current level
    //output: iv array
    public function calculate_inital_ivs(array $stat_values, int $lvl){

        if($this->characteristic != null){
            //determine highest iv values from characteristics
            $this->calculate_characteristic_iv($stat_values, $lvl);  //type error here?

            //determine the highest possible iv value
            $this->heighest_iv = max($this->possible_ivs[$this->heighest_iv_index]);
        }

        //loop through remaining stats and test each iv, add the valid ones the possible iv array
        foreach(self::STAT_INDEXES as $stat_index){

            if($this->possible_ivs[$stat_index] == null){

                $reached_bound = false; //keeps track of lower bound of working ivs so that we know when to start and stop iteration ivs
                $possible = [];
                for($i=0; $i<=$this->heighest_iv; $i++){
                    if($this->test_iv($i, $stat_index, $stat_values[$stat_index], $lvl, $this->heighest_iv)){
                        array_push($possible, $i);
                        //once we find the first iv that works we can set the bool to true so that once an invalid iv is found, we do not need to iterate anymore
                        if(!$reached_bound) $reached_bound=true; 
                    } 
                    else{
                        if($reached_bound) break;
                    }
                }

                //push possible
                $this->slot_possible_ivs($possible, $stat_index);
            }
        }

        $this->initial_level_calculated = true;

        // if($this->hidden_power_type != null){
        //     $this->hidden_power_filter();
        // }
    }

    //takes in the previously calculated array and filters it based on the new level data (this isn't actually necessary for the calculation, but rather to improve performance)
    //this function is intended to loop for each additional level entered by the user - THIS IS THE MAIN FUNCTION FOR THE SUCCESSIVE ITERATIONS
    //inputs: previous level output, current stat values, current level
    //output: iv array
    private function calculate_new_level_ivs(array $current_iv_ranges, array $stat_values, int $lvl){

        //calculate ivs from scratch to ensure new values
        foreach(self::STAT_INDEXES as $stat_index){

            $reached_bound = false; //keeps track of lower bound of working ivs so that we know when to start and stop iteration ivs
            $possible = [];
            for($i=0; $i<=$this->heighest_iv; $i++){
                if($this->test_iv($i, $stat_index, $stat_values[$stat_index], $lvl, $this->heighest_iv)){
                    array_push($possible, $i);
                    //once we find the first iv that works we can set the bool to true so that once an invalid iv is found, we do not need to iterate anymore
                    if(!$reached_bound) $reached_bound=true; 
                } 
                else{
                    if($reached_bound) break;
                }
            }

            //update the possible iv array to the intersect of the old and filtered arrays
            $this->possible_ivs[$stat_index] = array_values(array_intersect($possible, $this->possible_ivs[$stat_index]));
        }
    }

    //unkeys base stat list to produce six entry array
    //input: raw base stat array
    //output: simplified array
    public function unkey_base_stats(array $raw_base_stats){
        return array($raw_base_stats['hp'], $raw_base_stats['attack'], $raw_base_stats['defense'], $raw_base_stats['spatk'], $raw_base_stats['spdef'], $raw_base_stats['speed']);
    }

    //determines the highest iv value using the characteristic, filters to find the highest value using the correct modulus offset
    //inputs: current stat values, level
    protected function calculate_characteristic_iv(array $current_values, int $lvl){

        //get characteristic modulus offset
        $offset = $this->characteristic[1];
        $stat_index = $this->characteristic[0];

        //get possible iv values from modulus offset and check if they are possible
        $possible = [];
        for($i = $offset; $i<=31; $i+=5){
            if($this->test_iv($i, $stat_index, $current_values[$stat_index], $lvl, 31)){
                array_push($possible, $i);
            }
        }

        //append possible iv's to list, set max index and value
        $this->slot_possible_ivs($possible, $stat_index);
        $this->heighest_iv_index = $stat_index;
    }

    //tests a specific iv for a selected stat, basically we try to recalculate the stat with the iv we are trying to test and compare it to the actual stat
    //inputs: iv to test, stat index, current stat value, level, max iv value
    //output: bool that says if this iv value works
    protected function test_iv(int $test_iv, string $stat_index, int $current_value, int $lvl, int $max_value): bool{
        
        if($test_iv > $max_value) return false; //iv cannot be higher than max determined by characteristic

        if($stat_index == 'hp'){  //checking HP stat
            if( floor(0.01 * (2 * $this->base_stats[$stat_index] + $test_iv + floor(0.25 * $this->evs[$stat_index])) * $lvl) + $lvl + 10 == $current_value ){
                return true;
            }
            else return false;
        }
        else{ //checking non-HP stat
            if( floor((floor(0.01 * (2 * $this->base_stats[$stat_index] + $test_iv + floor(0.25 * $this->evs[$stat_index])) * $lvl) + 5) * $this->get_nature_delta($stat_index, $this->nature)) == $current_value){
                return true;
            }
            else return false;
        }
    }

    //gets the percent delta value of a nature for a specific stat
    //inputs: stat index
    //output: delta value
    public function get_nature_delta(string $stat): float {

        $nature_delta = 1;

        // $stats_list = ['attack' => '1', 'defense' => '2', 'spatk' => '3', 'spdef' => '4', 'speed' => '5'];
        // $stat_key = $stats_list[$stat];

        // if($nature[0] == $stat_key) $nature_delta += 0.1;
        // if($nature[1] == $stat_key) $nature_delta -= 0.1;

        if (self::NATURE_EFFECTS[$this->nature]['up'] === false) {
            return 1;
        }

        if (self::NATURE_EFFECTS[$this->nature]['up'] == $stat) {
            $nature_delta += 0.1;
        }
        if (self::NATURE_EFFECTS[$this->nature]['down'] == $stat) {
            $nature_delta -= 0.1;
        }

        return $nature_delta;
    
    }

    //analyses current possible ivs to determine which next level would narrow down the possible ivs for a given stat by index
    //inputs: stat index, current ranges, most recent level
    //output: int value signifying next level to check ivs
    public function estimate_next_narrowing_level(string $stat_index, array $current_iv_ranges, int $last_calculated_level) : int{
        
        //the following loop will produce an array containing the possible next stat values at each next level, if there values are varying then that level will return

        if (count($current_iv_ranges[$stat_index]) < 1) {
            return -1;
        }

        $current_level = $last_calculated_level + 1;

        for(; $current_level < 100; $current_level++) {
            $stat_values= [];

            //var_dump($this); print_r($current_iv_ranges); die();
            if (!is_array($current_iv_ranges[$stat_index])) {
                continue;
            }
            foreach($current_iv_ranges[$stat_index] as $iv){ //calculate stat with each iv in the array at every level
                array_push($stat_values, $this->calculate_stat($stat_index, $this->base_stats[$stat_index], $iv, $this->evs[$stat_index], $current_level, $this->nature));
            }

            if(max($stat_values) != min($stat_values)) {
                return $current_level;
            }
        }

        return 101; // confirmed to be that IV; handle in client-side

    }

    protected function hidden_power_filter(){
    
        /* // NOTE: These are using the 2-char abbreviations
        HP	1	0.238095238
        AT	2	0.476190476
        DF	4	0.952380952
        SP	8	1.904761905
        SA	16	3.80952381
        SD	32	7.619047619

        These numbers are based on STAT * MULTIPLIER * 15 / 63 as outlined on Bulba.
        Technically the sum of all stats * their multipliers is what gets the
        `sum * 15 / 63` formula, but since we're adding these up later to help rule
        OUT types, we can just take their individual sums of `stat * 15 / 63` and
        add them up later; same result. (The final number gets floor()'d.)

        HP + AT + DF + SP + SA = 7.380952381    // All stats are ODD if being added here
        if !SD (even|0) above, then type has to be <= 7; if SD (odd|1) above, type has to be Dark (15)
        also if SD alone is odd, type has to be >= 7

        ^ the reason for this is SD's "typemath" is 7.6, so if all other numbers are
        confirmed to be ONLY odd, they add 7.38095 to the "typemath" sum, meaning the
        type HAS to have an index <= 7 if SD is even (0) or HAS to be Dark (15) if SD
        is odd (1).

        HP + AT + DF + SP + SD = 11.19047619
        if !SA (even|0), then type has to be <= 11; if SA alone (odd), type has to be >= 3
        */
        $typemath_array = ['hp' => 0.238095238, 'attack' => 0.476190476, 'defense' => 0.952380952, 'speed' => 1.904761905, 'spatk' => 3.80952381, 'spdef' => 7.619047619];

        $possible_types = []; // This contains all possible types for the Hidden Power based on potential IVs.
        
        // This is the "odd/even" sum, or basically an array that is meant to keep track of possible HPow type odd/even combinations. They all get added at the same time to this array.
        $oe_sum = [
            'hp'      => [],
            'attack'  => [],
            'defense' => [],
            'speed'   => [],
            'spatk'   => [],
            'spdef'   => []
        ];

        $ivs = $this->possible_ivs;

        foreach(self::STAT_INDEXES as $stat_index) {

            // Set up an array for each stat to track if ANY of its IVs are odd or
            // even. Initialize it now (not in the old code) so it can be checked later.
            $iv_odd_or_even[$stat_index] = [
                'odd' => false,
                'even' => false
            ];
            if (!is_array($ivs[$stat_index])) {
                continue; // No possible IVs (error), so go to the next stat
            }
        
            // Now let's loop through each possible IV for this stat.
            // You could use a for() loop here instead if you want; basically we're just
            // looping through a basic array. For this example, I'm using foreach() and
            // each IV for the stat will be represented as $iv.
            foreach($ivs[$stat_index] as $iv) {
                if ($iv % 2 === 0) {
                    $iv_odd_or_even[$stat_index]['even'] = true;
                } else {
                    $iv_odd_or_even[$stat_index]['odd'] = true;
                }
                
                /*
                
                This part is JUST for the HPow power formula, since we're already looping
                through each IV and each stat as well. It's not necessary for this.

                if ($iv % 4 == 2 || $iv % 4 == 3) {
                    $iv_oddeven[$stat]['damage_y'] = true;
                } else {
                    $iv_oddeven[$stat]['damage_n'] = true;
                }

                This part is just checking to see if all four keys ('odd', 'even',
                'damage_y', and 'damage_n') are set and, if they are, end the loop for
                that stat. Of course this is based on the old code where $iv_odd_or_even
                was not initialized, so keep that in mind...

                if (count($iv_oddeven[$stat]) >= 4) {
                    break;
                }

                Actually, in hindsight, it's better to still check to see if both
                even and odd are represented because there's no need to loop through
                more of that stat's IVs if it won't change anything.
                 */

                if (count($iv_odd_or_even[$stat_index]) >= 2) {
                    break; //move onto next stat
                }
            }
        }

        // At this point, you now should have an array in $iv_odd_or_even[@STAT] for
        // each stat, and it will have 'even' and 'odd' as boolean key=>value pairs.

        // The next step is "the big HPow loop". It's a big loop, going through each of
        // the six stats in a nested sequence and then getting a sum of how many
        // possible IVs are odd or even in the stat. In the original code, this is
        // represented by $oe_sum[@STAT][], which will have 1 or 2 array members and
        // each value is either 0 or 1, depending on if the stat is odd or even for
        // that loop iteration. This is then checked later.

        // For clarity, each ITERATOR in this loop will be prefixed with `oe_`, which
        // stands for OddEven. This is NOT for its actual HP stat, just the iterator,
        // which starts at 0 at the beginning of each for loop and will be 1 after the
        // first loop, then end after the second loop (where 2 <= 1 is no longer true).

        // The first pass of each loop is to check for EVEN values (0), while the second
        // pass is to check for ODD values (1). $oe_XX will only ever be 0 or 1, and the
        // number it currently is represents what it's checking for.
        for($oe_hp = 0; $oe_hp <= 1; $oe_hp++) {

            // If EITHER of these conditions are true, continue the loop without adding
            // anything to the $oe_sum array (explained later).
            if (
                // Checking for EVEN number, but $iv_odd_or_even[@STAT]['even'] is FALSE
                ($oe_hp === 0 && !$iv_odd_or_even[$stat_index]['even'])
                // Checking for ODD number, but $iv_odd_or_even[@STAT]['odd'] is FALSE
                || ($oe_hp === 1 && !$iv_odd_or_even[$stat_index]['odd'])
            ) {
                // Skip this check to move on to the next one, since this condition is
                // definitely false
                continue;
            }

            // At this point, we know that this check to see if HP is even (0) or odd
            // (1) is true, so we continue to check for the next stat. It essentially
            // forms a matrix of checking every unique possibility of IVs that are odd
            // or even for all stats, BUT ending that iteration early if it does not
            // line up with the possible IVs odd-ness or even-ness determined earlier
            // on.
            // Because of this, it's time to move on to the next for loop, which is the
            // same as HP, except for Attack. Keep in mind it should probably follow the
            // HP > Attack > Defense > SPEED > Sp. Atk > Sp. Def order just to stay
            // consistent with the IV/HPow formula.

            // Check ATTACK
            for($oe_attack = 0; $oe_attack <= 1; $oe_attack++) {
                if (
                    ($oe_attack === 0 && !$iv_odd_or_even['attack']['even'])
                    || ($oe_attack === 1 && !$iv_odd_or_even['attack']['odd'])
                ) {
                    continue;
                }

                // Check DEFENSE
                for($oe_defense = 0; $oe_defense <= 1; $oe_defense++) {
                    if (
                        ($oe_defense === 0 && !$iv_odd_or_even['defense']['even'])
                        || ($oe_defense === 1 && !$iv_odd_or_even['defense']['odd'])
                    ) {
                        continue;
                    }

                    // Check SPEED
                    for($oe_speed = 0; $oe_speed <= 1; $oe_speed++) {
                        if (
                            ($oe_speed === 0 && !$iv_odd_or_even['speed']['even'])
                            || ($oe_speed === 1 && !$iv_odd_or_even['speed']['odd'])
                        ) {
                            continue;
                        }

                        // Check SP. ATK
                        for($oe_spatk = 0; $oe_spatk <= 1; $oe_spatk++) {
                            if (
                                ($oe_spatk === 0 && !$iv_odd_or_even['spatk']['even'])
                                || ($oe_spatk === 1 && !$iv_odd_or_even['spatk']['odd'])
                            ) {
                                continue;
                            }

                            // Check SP. DEF (the final stretch)
                            for($oe_spdef = 0; $oe_spdef <= 1; $oe_spdef++) {
                                if (
                                    ($oe_spdef === 0 && !$iv_odd_or_even['spdef']['even'])
                                    || ($oe_spdef === 1 && !$iv_odd_or_even['spdef']['odd'])
                                ) {
                                    continue;
                                }

                                // OKAY. FINALLY. If the loop has gotten this far, that
                                // means everything lines up so it's possible for this
                                // iteration to work.
                                // Sample data to visualize what got us here:

        /* // COMMENT LINE
                                // Whether stats have an even or odd IV present
                                $iv_odd_or_even = [
                                    'hp'      => ['even' => true,  'odd' => false],
                                    'attack'  => ['even' => true,  'odd' => true],
                                    'defense' => ['even' => false, 'odd' => true],
                                    'speed'   => ['even' => true,  'odd' => false],
                                    'spatk'   => ['even' => false, 'odd' => true],
                                    'spdef'   => ['even' => true,  'odd' => false]
                                ];
                                // NOTE: false/false is only possible for invalid input

                                // This means the first time we got to this point, it
                                // should be this:
                                $oe_hp      == 0; // first loop
                                $oe_attack  == 0; // again, first loop
                                $oe_defense == 1; // failed first try, succeeded 2nd (odd)
                                $oe_speed   == 0;
                                $oe_spatk   == 1; // failed first try when it got here
                                $oe_spdef   == 0; // this is even

                                // Because of this, it means we're at: (for this loop)
                                // HP      == EVEN
                                // Attack  == EVEN
                                // Defense == ODD
                                // Speed   == EVEN
                                // Sp. Atk == ODD
                                // Sp. Def == EVEN
        */ // END COMMENT LINE
                                // At THIS point, we can now get a possible HPow type.
                                // This is done by getting a sum from "typemath" further
                                // back of all of the ODD types.
                                $type_index = floor(
                                ($typemath_array['hp']      * $oe_hp)     // 0
                                + ($typemath_array['attack']  * $oe_attack) // 0
                                + ($typemath_array['defense'] * $oe_defense)// 0.952380952
                                + ($typemath_array['speed']   * $oe_speed)  // 0
                                + ($typemath_array['spatk']   * $oe_spatk)  // 3.80952381
                                + ($typemath_array['spdef']   * $oe_spdef)  // 0
                                ); // end the floor() part
                                // floor(4.761904762) == 4 == 'rock'
                                
                                // Ignore this possibility if a Hidden Power type was
                                // supplied and it is different from this result's
                                // Hidden Power.
                                if ($this->hidden_power_type != null && $this->hidden_power_type != $type_index) {continue; }  //unsure about this line
                                // ^ for the above, keep in mind $INPUT['hp_type'] needs
                                // to be 0-15, not a slug, and is what the user inputs
                                // from the frontend.
                                // Basically this bit just doesn't bother adding this
                                // IV possibility if the HPow type doesn't match the
                                // result; it rules it out.

                                //// ORIGINAL COMMENT BELOW --- VVVVV
                                // Now add a 0 or 1 to a running array of possible
                                // types. If it ends up being 0 in the end, then it must
                                // be exclusively even numbers; if it ends up being 2 in
                                // the end, it must be exclusively odd numbers; if it
                                // ends up being 1 in the end, it can be either even or
                                // odd.
                                //// --------------------------
                                //// NEWER COMMENT BELOW ------ VVVVV
                                // I'm actually not 100% sure on the significance of
                                // this vs. $iv_odd_or_even, BUT I think this
                                // establishes a confirmed matrix that leads to this
                                // specific type being true, as all of these are set
                                // at the same time and then filtered out later.
                                $oe_sum['hp'][]      = ($oe_hp)      ? 1 : 0;
                                $oe_sum['attack'][]  = ($oe_attack)  ? 1 : 0;
                                $oe_sum['defense'][] = ($oe_defense) ? 1 : 0;
                                $oe_sum['speed'][]   = ($oe_speed)   ? 1 : 0;
                                $oe_sum['spatk'][]   = ($oe_spatk)   ? 1 : 0;
                                $oe_sum['spdef'][]   = ($oe_spdef)   ? 1 : 0;

                                // This part used to be the $matrix_types stuff, but
                                // really it wasn't necessary. This works. It will
                                // filter out unique values later.
                                $possible_types[] = $type_index;
                                
                            } // end $oe_spdef loop
                            
                        } // end $oe_spatk loop

                    } // end $oe_speed loop
                    
                } // end $oe_defense loop

            } // end $oe_attack loop

        } // end $oe_hp loop

        // Ok, ALMOST done here, but first we need to check to see if the user supplied
        // their own HPow Type.
        if ($this->hidden_power_type != null) {

            foreach(['hp', 'attack', 'defense', 'speed', 'spatk', 'spdef'] as $stat) {
                $stat_index = $stat;   

                if (count($oe_sum[$stat]) === 0) { //potential issue here
                    continue; // Can probably be break in all honesty since all are
                            // likely unset at this point, right?
                }

                // Filter out any duplicate values, as we only need to know if there's
                // a 0, 1, or both a 0 and 1 for this stat.
                $oe_sum[$stat] = array_unique($oe_sum[$stat]);

                // We can just skip it if it has both odd and even stats, since that
                // doesn't help reduce anything.
                if (count($oe_sum[$stat]) >= 2) {
                    continue;
                }

                // For readability, we'll use an if statement instead of trying to have
                // the ternary operator deciding which function to call, and also use
                // anonymous functions here.
                if ($oe_sum[$stat][0] === 1) {
                    // Since $oe_sum[$stat] contains just ONE value now, if it's 1, it
                    // means it's odd, while if it's 0, it's even.

                    // array_filter is removing any items that don't match the criteria.
                    $ivs[$stat_index] = array_filter($ivs[$stat_index], function($v) {
                        return $v & 1; // isOdd
                    });
                } else {
                    $ivs[$stat_index] = array_filter($ivs[$stat_index], function($v) {
                        return !($v & 1); // isEven
                    });
                }

                $this->possible_ivs = $ivs;
            }
        }
    }

    //calculates stat value
    //input: stat index, base stat, iv, ev, lvl, nature 
    //output: int stat value
    protected function calculate_stat(string $stat_index, int $base_stat, int $iv, int $ev, int $lvl, string $nature){

        if($stat_index == 'hp'){  //checking HP stat
            return floor(0.01 * (2 * $base_stat + $iv + floor(0.25 * $ev)) * $lvl) + $lvl + 10 ;
        }

        else{ //checking non-HP stat
            return floor((floor(0.01 * (2 * $base_stat + $iv + floor(0.25 * $ev)) * $lvl) + 5) * $this->get_nature_delta($stat_index, $nature));
        }
    }

}