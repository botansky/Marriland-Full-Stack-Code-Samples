<?php declare(strict_types=1);
namespace Marriland\IV_Calculator;

/**
 * Calculation of the Pokémon's Hidden Power and possible IVs based on HPow
 * 
 * This is a bit of a two-in-one deal, as it both can help narrow down a
 * Pokémon's possible IVs as well as figure out possible Hidden Power types it
 * can possibly have based on its possible IVs, depending on whether the user
 * inputs their Hidden Power or not.
 * 
 * This is essentially a self-contained class that requires very little to do
 * its thing. It is meant to be invoked in the `IV_Calculator` class like so:
 * 
 * ```
 * $hpow = new Hidden_Power_IVs($possible_ivs, $user_hidden_power_type);
 * $possible_ivs = $hpow->get_hidden_power_filtered_ivs();
 * $possible_hpow_types = $hpow->get_possible_hidden_power_types();
 * ```
 */
class Hidden_Power_IVs {
    /**
     * Types according to Pokémon's internal index order, minus Normal/Fairy.
     * The order and numbering are EXTREMELY important when it comes to
     * calculating a Pokémon's possible Hidden Power (HPow) type and also using
     * that to deduce IVs, so don't reorder or adjust this.
     * 
     * When using the HPow formula, it adds values based on whether each IV is
     * odd or even and multiplies it by the stat. This will result in a range of
     * 0-15, with 15 being `1/1/1/1/1/1` _(odd/odd/odd/odd/odd/odd)_ and
     * resulting in being `dark`, while 0 being `0/0/0/0/0/0` and resulting in
     * `fighting`.
     * 
     * The "type math sum" (determined later), which uses the table from
     * `TYPE_MATH_ARRAY`, can help rule out possible types. For instance, if the
     * type math sum is `6`, that means the only possible HPow types are
     * `fighting`, `flying`, `poison`, `ground`, `rock`, `bug`, and `ghost`, so
     * any other IV combinations can be ignored.
     * 
     * @var string[]
     */
    private const INTERNAL_TYPES = [
         0 => 'fighting',
         1 => 'flying',
         2 => 'poison',
         3 => 'ground',
         4 => 'rock',
         5 => 'bug',
         6 => 'ghost',
         7 => 'steel',
         8 => 'fire',
         9 => 'water',
        10 => 'grass',
        11 => 'electric',
        12 => 'psychic',
        13 => 'ice',
        14 => 'dragon',
        15 => 'dark'
    ];

    /**
     * The "type math" definitions for each stat
     * 
     * This is actually pretty clever, now that I look at it. Since the HPow
     * type is determined from a formula, you can weed out any types that don't
     * match if the sum of values are in a range.
     * 
     * `````
     * STAT    MULTIPLIER  MULTI * 15 / 63
     * HP            1       0.238095238
     * ATTACK        2       0.476190476
     * DEFENSE       4       0.952380952
     * SPEED         8       1.904761905
     * SP. ATK      16       3.80952381
     * SP. DEF      32       7.619047619
     * `````
     * 
     * These numbers are based on `STAT [=0|1] * MULTIPLIER * 15 / 63` as
     * outlined on Bulba. Technically the sum of all stats * their multipliers
     * is what gets the `sum * 15 / 63` formula, but since we're adding these
     * up later to help rule OUT types, we can just take their individual sums
     * of `stat * 15 / 63` and add them up later; same result. (The final
     * number gets floor()'d.)
     * 
     * **EXAMPLE:**
     * + HP  + ATK + DEF + SPD + SAT = 7.380952381   (SDF = 0 = even)
     * > All stats are ODD if being added here if !SDF (even|0) above, then type
     *   has to be <= 7; if SDF (odd|1) above, type has to be Dark (15) also if
     *   SDF alone is odd, type has to be >= 7
     * 
     * ^ the reason for this is SDF's "type math" is `7.6`, so if all other
     * numbers are confirmed to be ONLY odd, they add `7.38095` to the "type
     * math" sum, meaning the type HAS to have an index <= `7` if SDF is even
     * (0) or HAS to be Dark (15) if SDF is odd (1).
     * 
     * **EXAMPLE:**
     * + HP  + ATK + DEF + SPD + SDF = 11.19047619   (SAT = 0 = even)
     * > If !SAT (even|0), then type has to be <= 11; if SA alone (odd),
     *   type has to be >= 3
     * 
     * Later on, during "the HPow loop" where it checks the even/odd
     * distribution of each stat, these sums can be used to rule out certain
     * types. As the examples above suggest, if you multiply the ODD numbers
     * for each IV possibility with the numbers in the table above, you'll get
     * a value.
     * 
     * @var float[]
     */
    private const TYPE_MATH_ARRAY = [
        'hp'      => 0.238095238,
        'attack'  => 0.476190476,
        'defense' => 0.952380952,
        'speed'   => 1.904761905,
        'spatk'   => 3.80952381,
        'spdef'   => 7.619047619
    ];

    /**
     * Possible types that the Pokémon can be. Stored as slugs.
     * 
     * @var string[]
     */
    private $possible_types = [];

    /**
     * If the user knows their Hidden Power type, that can be very helpful for
     * ruling out certain IVs. This is passed into the constructor, but is
     * assumed to be null/unset.
     * 
     * @var string|null
     */
    private $user_hidden_power_type;

    /**
     * All of the possible IVs of the Pokémon coming in from the `IV_Calculator`
     * class. These are used to determine potential HPow types in case it wasn't
     * defined by the user.
     * 
     * @var array[]
     */
    private $possible_ivs = [
        'hp'      => [],
        'attack'  => [],
        'defense' => [],
        'speed'   => [],
        'spatk'   => [],
        'spdef'   => []
    ];

    /**
     * Keeps track of each stat and whether there is an `odd` and/or an `even`
     * value present. This gets generated during `__construct()`.
     * 
     * **EXAMPLE:**
     * ```
     * $iv_odd_or_even = [
     *    'hp' => [
     *       'odd' => false,
     *       'even' => true
     *    ],
     *    // ...
     * ];
     * ```
     */
    private $iv_odd_or_even = [
    ];

    /**
     * This is the "odd/even" sum, or basically an array that is meant to keep
     * track of possible HPow type odd/even combinations. They all get added at
     * the same time to this array.
     * 
     * @var array[] Odd/Even sum
     */
    private $oe_sum = [ // Odd/Even Sum
        'hp'      => [],
        'attack'  => [],
        'defense' => [],
        'speed'   => [],
        'spatk'   => [],
        'spdef'   => []
    ];

    /**
     * @param array[] $possible_ivs Array of possible ivs for each stat in a
     *                            nested array (e.g. `'hp' => [0, 1, 2]`).
     * @param string|null $user_hidden_power_type If the user input a Hidden
     *                         Power type, this can be used to help determine
     *                         IVs. If `null`, this is ignored.
     */
    public function __construct(array $possible_ivs, ?string $user_hidden_power_type = null) {
        if (isset($user_hidden_power_type) && in_array($user_hidden_power_type, array_values(self::INTERNAL_TYPES), true)) {
            $this->user_hidden_power_type = $user_hidden_power_type;
        }

        foreach(array_keys($this->possible_ivs) as $stat) {
            $this->iv_odd_or_even[$stat] = [
                'odd' => false,
                'even' => false
            ];
            if (isset($possible_ivs[$stat]) && is_array($possible_ivs[$stat])) {
                $this->possible_ivs[$stat] = $possible_ivs[$stat];
            }
        }

        $this->check_odd_even_ivs();
        $this->the_big_hpow_loop();
    }

    /**
     * Check odd and even possible IVs
     * 
     * This checks through all of the supplied possible IVs and determines, for
     * each stat, whether `even` or `odd` values are present in the possible IVs
     * for the stat.
     * 
     * @return void
     */
    private function check_odd_even_ivs(): void {
        foreach($this->possible_ivs as $stat => $ivs) {
            if (!is_array($ivs) || count($ivs) === 0) {
                continue; // No possible IVs, so go to the next stat.
            }

            // Now let's loop through each possible IV for this stat.
            foreach($ivs as $iv) {
                if ($iv % 2 === 0) {
                    $this->iv_odd_or_even[$stat]['even'] = true;
                } else {
                    $this->iv_odd_or_even[$stat]['odd'] = true;
                }

                if ($this->iv_odd_or_even[$stat]['even'] === true && $this->iv_odd_or_even[$stat]['odd'] === true) {
                    break; // No need for further loops since both are true.
                }
            }
        }
    }

    /**
     * This is "the big HPow loop"
     * 
     * I honestly couldn't think of a better name for it. :V
     * 
     * This starts up a 6-level for loop that loops through up to twice for each
     * stat, so a total of 64 potential iterations. This is important because
     * what determines Hidden Power types is whether IVs are odd or even, so
     * this essentially creates a matrix of which stats have possible odd IVs
     * and which have possible even IVs, and then uses some type math magic
     * to help narrow down possible types or rule out potential IV results.
     * 
     * It's a big loop, going through each of the six stats in a nested
     * sequence and then getting a sum of how many possible IVs are odd or even
     * in the stat. In the original code from the old site, this was
     * represented by $oe_sum[$stat][], which will have 1 or 2 array members and
     * each value is either 0 or 1, depending on if the stat is odd or even for
     * that loop iteration. This is then checked later.
     * 
     * For clarity, each ITERATOR in this loop will be prefixed with `oe_`,
     * which stands for OddEven. This is NOT for its actual HP stat, just the
     * iterator, which starts at 0 at the beginning of each for loop and will
     * be 1 after the first loop, then end after the second loop (where 2 <= 1
     * is no longer true).
     * 
     * The first pass of each loop is to check for EVEN values (0), while the
     * second pass is to check for ODD values (1). $oe_XX will only ever be 0
     * or 1, and the number it currently is represents what it's checking for.
     */
    private function the_big_hpow_loop(): void {

        for($oe_hp = 0; $oe_hp <= 1; $oe_hp++) { // start HP

            // If EITHER of these conditions are true, continue the loop 
            // without adding anything to the `$oe_sum` array (explained later).
            if (
                // Checking for EVEN number, but
                // $this->iv_odd_or_even[$stat]['even'] is FALSE
                ($oe_hp === 0 && $this->iv_odd_or_even['hp']['even'] === false)
                // Checking for ODD number, but
                // $this->iv_odd_or_even[$stat]['odd'] is FALSE
                || ($oe_hp === 1 && !$this->iv_odd_or_even['hp']['odd'])
            ) {
                // Skip this check to move on to the next one, since this
                // condition is definitely false
                continue;
            }

            /*
        	At this point, we know that this check to see if HP is even (0) or
            odd (1) is true, so we continue to check for the next stat. It
            essentially forms a matrix of checking every unique possibility of
            IVs that are odd or even for all stats, BUT ending that iteration
            early if it does not line up with the possible IVs odd-ness or
            even-ness determined earlier on.

	        Because of this, it's time to move on to the next for loop, which
            is the same as HP, except for Attack. Keep in mind it should
            probably follow the HP > Attack > Defense > SPEED > Sp. Atk > Sp.
            Def order just to stay consistent with the IV/HPow formula.
            */

            // Check ATTACK
            for($oe_attack = 0; $oe_attack <= 1; $oe_attack++) {
                if (
                    ($oe_attack === 0 && !$this->iv_odd_or_even['attack']['even'])
                    || ($oe_attack === 1 && !$this->iv_odd_or_even['attack']['odd'])
                ) {
                    continue;
                }

        		// Check DEFENSE
                for($oe_defense = 0; $oe_defense <= 1; $oe_defense++) {
                    if (
                        ($oe_defense === 0 && !$this->iv_odd_or_even['defense']['even'])
                        || ($oe_defense === 1 && !$this->iv_odd_or_even['defense']['odd'])
                    ) {
                        continue;
                    }

                    // Check SPEED
                    for($oe_speed = 0; $oe_speed <= 1; $oe_speed++) {
                        if (
                            ($oe_speed === 0 && !$this->iv_odd_or_even['speed']['even'])
                            || ($oe_speed === 1 && !$this->iv_odd_or_even['speed']['odd'])
                        ) {
                            continue;
                        }

                        // Check SP. ATK
                        for($oe_spatk = 0; $oe_spatk <= 1; $oe_spatk++) {
                            if (
                                ($oe_spatk === 0 && !$this->iv_odd_or_even['spatk']['even'])
                                || ($oe_spatk === 1 && !$this->iv_odd_or_even['spatk']['odd'])
                            ) {
                                continue;
                            }

                            // Check SP. DEF (the final stretch)
                            for($oe_spdef = 0; $oe_spdef <= 1; $oe_spdef++) {
                                if (
                                    ($oe_spdef === 0 && !$this->iv_odd_or_even['spdef']['even'])
                                    || ($oe_spdef === 1 && !$this->iv_odd_or_even['spdef']['odd'])
                                ) {
                                    continue;
                                }

                                // OK now we can count this combination.
                                // This is moved to a separate method.
                                $this->the_end_of_the_hpow_loop([
                                    'hp'      => $oe_hp,
                                    'attack'  => $oe_attack,
                                    'defense' => $oe_defense,
                                    'speed'   => $oe_speed,
                                    'spatk'   => $oe_spatk,
                                    'spdef'   => $oe_spdef
                                ]);

                            } // end $oe_spdef loop

                        } // end $oe_spatk loop

                    } // end $oe_speed loop

                } // end $oe_defense loop

            } // end $oe_attack loop

        } // end $oe_hp loop
    }

    /**
     * "The end of the HPow Loop"
     * 
     * As weird of a name it may be, it describes it well. Way better separating
     * this to a separate method than doing all sorts of stuff while nested six
     * levels deep in a wild loop.
     * 
     * Everything in here runs once for every time a valid odd/even combination
     * for IVs have been found (so every for() loop passes). It accepts an array
     * of the CURRENT odd/even test it's doing for each stat as a
     * `slug => value` pair.
     * 
     * **EXAMPLE:**
     * ```
     * $oe = [
     *      'hp'      => 1,
     *      'attack'  => 0,
     *      'defense' => 0,
     *      'speed'   => 1,
     *      'spatk'   => 0,
     *      'spdef'   => 1
     * ];
     * ```
     * 
     * In the example above, this means that the IV combination tested right now
     * matches the pattern of `(1 = odd, 0 = even)`. These are then used for
     * other purposes depending on if the user has supplied a Hidden Power type
     * or not.
     * 
     * @param array $oe Odd/Even values for each stat
     * @return bool Not really used for much, but `false` means the user's
     *              Hidden Power type was a match, so no additional checks need
     *              to be done.
     */
    private function the_end_of_the_hpow_loop(array $oe): bool {
        $type_index = $this->get_type_index($oe);

        if (isset($this->user_hidden_power_type) && $this->user_hidden_power_type === $type_index) {
            $this->add_to_oe_sums($oe);
            return false;
        }

        $this->add_to_possible_types($type_index);
        return true;
    }

    /**
     * Get the Type Index (as a slug) based on the Type Math
     * 
     * This takes in the "OE" Odd/Even array for the iteration of the "big HPow
     * loop" and then applies the multipliers found in `TYPE_MATH_ARRAY` to each
     * stat to get a number. The number represents a type, and that is what is
     * returned.
     * 
     * @param array $oe Odd/Even values for each stat
     * @return string Slug of the type that matches the type index
     */
    private function get_type_index(array $oe): string {
        // At THIS point, we can now get a possible HPow type.
        // This is done by getting a sum from "typemath" further
        // back of all of the ODD types.

        $type_index = floor(
              (self::TYPE_MATH_ARRAY['hp']      * $oe['hp'])     // 0
            + (self::TYPE_MATH_ARRAY['attack']  * $oe['attack']) // 0
            + (self::TYPE_MATH_ARRAY['defense'] * $oe['defense'])// 0.952380952
            + (self::TYPE_MATH_ARRAY['speed']   * $oe['speed'])  // 0
            + (self::TYPE_MATH_ARRAY['spatk']   * $oe['spatk'])  // 3.80952381
            + (self::TYPE_MATH_ARRAY['spdef']   * $oe['spdef'])  // 0
        ); // end the floor() part
        // floor(4.761904762) == 4 == 'rock'

        return self::INTERNAL_TYPES[$type_index];
    }

    /**
     * Add to the "Odd/Even" (OE) sums
     * 
     * This is a bit odd (no pun intended), but basically every iteration of
     * testing possible IV odd/even combinations in "the big HPow loop" will
     * result in this method being called, assuming the user inputted a Hidden
     * Power type to help narrow down their possible IVs.
     * 
     * It adds to the `$this->oe_sum` property for each stat either a 1 or 0
     * (as a new array member). This is a running tally of every time a test
     * has been odd (1) or even (0).
     * 
     * That's all _this_ method does. In `get_hidden_power_filtered_ivs()`, it
     * then grabs the unique values for each stat (which will be 0-2
     * possibilities, considering empty is technically possible aka an invalid
     * calculation). If there are two members in the array ([0, 1]), then each
     * IV for that stat could be either odd or even; otherwise, this is used to
     * filter those results into either only odd or only even.
     * 
     * Again, see `get_hidden_power_filtered_ivs()` for a better example, but
     * just know that's why this is important.
     * 
     * @param array $oe Odd/Even values for each stat
     * @return void
     * @see get_hidden_power_filtered_ivs()
     */
    private function add_to_oe_sums(array $oe): void {
        $this->oe_sum['hp'][]      = ($oe['hp'])      ? 1 : 0;
        $this->oe_sum['attack'][]  = ($oe['attack'])  ? 1 : 0;
        $this->oe_sum['defense'][] = ($oe['defense']) ? 1 : 0;
        $this->oe_sum['speed'][]   = ($oe['speed'])   ? 1 : 0;
        $this->oe_sum['spatk'][]   = ($oe['spatk'])   ? 1 : 0;
        $this->oe_sum['spdef'][]   = ($oe['spdef'])   ? 1 : 0;
        return;
    }

    /**
     * Add a type to the list of possible Hidden Power types
     * 
     * Fairly basic "setter" (though not intended to be used publicly), this
     * is used to add to the list of possible types and also performs any other
     * tasks to keep those types tidy, such as removing duplicate entries.
     * 
     * @param string $type The type to add. Needs to be a type slug.
     * @return void
     */
    private function add_to_possible_types(string $type): void {
        $this->possible_types[] = $type;
        $this->possible_types = array_unique($this->possible_types);
    }

    /**
     * Get the filtered IVs for a supplied Hidden Power type
     * 
     * If the user has submitted a confirmed Hidden Power type, this can be used
     * to filter out any IVs that do not line up with the type math for that
     * Hidden Power type.
     * 
     * **EXAMPLE:**
     * ```
     * $calc = new Hidden_Power_IVs($ivs, $hidden_power);
     * $ivs = $calc->get_hidden_power_filtered_ivs();
     * ```
     * 
     * It does not require any inputs and instead relies on the IVs that were
     * passed in to the constructor, although you can pass in an array of
     * possible IVs manually if you'd rather or if this method gets called far
     * after the `Hidden_Power_IVs` object is created.
     * 
     * Certain Hidden Power types have a stronger "weight" than others, which
     * can be seen in the `TYPE_MATH_ARRAY` values for IVs and comparing that
     * to the order in which the types appear in the `INTERNAL_TYPES` array. For
     * example, a supplied Hidden Power of `dark` will almost certainly filter
     * out any even numbers while a "weaker" Hidden Power type like Fighting or
     * Flying will have a relatively negligible effect. This is intended
     * behavior.
     * 
     * @param array|null $possible_ivs Optional. If supplied with a multi-level
     * array of possible IVs, it will use that instead of the default of using
     * whatever is present in the `Hidden_Power_IVs` object.
     * @return array A filtered multi-level array of possible IVs for each stat,
     * after applying the Hidden Power filters.
     */
    public function get_hidden_power_filtered_ivs(?array $possible_ivs = null): array {
        if (!isset($possible_ivs)) {
            // Default behavior is to grab the IVs that already exist in the
            // object.
            $possible_ivs = $this->possible_ivs; // Don't work on the _property_
        }

        if (!isset($this->user_hidden_power_type)) {
            // The user didn't set a HPow type, so there's no point in filtering
            return $possible_ivs;
        }

        foreach(array_keys($this->possible_ivs) as $stat) {
            if (count($this->oe_sum[$stat]) === 0) {
                continue; // Can probably be break in all honesty since all are
                          // likely unset at this point, right?
            }

            // Filter out any duplicate values, as we only need to know if
            // there's a 0, 1, or both a 0 and 1 for this stat.
            $oe_sum_values = array_unique($this->oe_sum[$stat]);

            // We can just skip it if it has both odd and even stats, since that
		    // doesn't help reduce anything.
            if (count($oe_sum_values) >= 2) {
                continue;
            }

            if ($oe_sum_values[0] === 1) {
                // Since $oe_sum[$stat] contains just ONE value now, if it's 1,
                // it means it's odd, while if it's 0, it's even.
                $possible_ivs[$stat] = array_filter($possible_ivs[$stat], function($v) {
                    return $v & 1; // is ODD
                });
            } else {
                $possible_ivs[$stat] = array_filter($possible_ivs[$stat], function($v) {
                    return !($v & 1); // is EVEN
                });
            }
        }

        return $possible_ivs;
    }

    /**
     * Get an array of possible Hidden Power types
     * 
     * This is relevant only if the user didn't supply a Hideen Power type. It
     * can give a list of possible Hidden Power types that match the different
     * IV spreads. Some users might like to see this if they haven't determined
     * which Hidden Power type they have.
     * 
     * @return array An array of possible types as type slugs
     */
    public function get_possible_hidden_power_types(): array {
        return $this->possible_types;
    }
}