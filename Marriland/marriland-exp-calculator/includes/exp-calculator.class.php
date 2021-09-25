<?php
namespace Marriland\EXP_Calculator;

use Marriland\Pokemon\Common;
use Marriland\Pokemon\Pokemon as Pokemon; //# using a Pokemon object here
use Marriland\Utility as Utility;
use Twig\Environment as Twig_Environment;
use Marriland\SEO;
use MongoDB\Database as Database;

class EXP_Calculator {

    protected $generation = 8;

    protected $user_pokemon;
    protected $enemy_pokemon;
    // protected $user_rate = 'fast';
    // protected $enemy_base_yield = 36; //lowest possible yield for any pokemon (sunkern)

    protected $user_level = 1;
    protected $enemy_level = 1;

    protected $target_level = 100;
    protected $exp_to_next_level = null; //to disable by default

    //modifiers
    protected $trainer_battle = false;
    protected $lucky_egg = false;
    protected $affection_hearts = false;
    protected $pass_power_level = 0;
    protected $o_power_level = 0;
    protected $rotom_power = false; 
    protected $exp_charm = false; 

    protected $used_in_battle = false;
    protected $exp_share = false;
    // protected $exp_all = false;
    // protected $team_size = 1;

    protected $traded = false;
    protected $foreign = false;
    
    protected $passed_evolution_level = false;

    const EXP_REQUIRED_FOR_LEVEL = [
        'medium-fast' => array(0,0,8, 27, 64, 125, 216, 343, 512, 729, 1000, 1331, 1728, 2197, 2744, 3375, 4096, 4913, 5832, 6859, 8000, 9261, 10648, 12167, 13824, 15625, 17576, 19683, 21952, 24389, 27000, 29791, 32768, 35937, 39304, 42875, 46656, 50653, 54872, 59319, 64000, 68921, 74088, 79507, 85184, 91125, 97336, 103823, 110592, 117649, 125000, 132651, 140608, 148877, 157464, 166375, 175616, 185193, 195112, 205379, 216000, 226981, 238328, 250047, 262144, 274625, 287496, 300763, 314432, 328509, 343000, 357911, 373248, 389017, 405224, 421875, 438976, 456533, 474552, 493039, 512000, 531441, 551368, 571787, 592704, 614125, 636056, 658503, 681472, 704969, 729000, 753571, 778688, 804357, 830584, 857375, 884736, 912673, 941192, 970299, 1000000),
        'erratic' => array(0,0,15, 52, 122, 237, 406, 637, 942, 1326, 1800, 2369, 3041, 3822, 4719, 5737, 6881, 8155, 9564, 11111, 12800, 14632, 16610, 18737, 21012, 23437, 26012, 28737, 31610, 34632, 37800, 41111, 44564, 48155, 51881, 55737, 59719, 63822, 68041, 72369, 76800, 81326, 85942, 90637, 95406, 100237, 105122, 110052, 115015, 120001, 125000, 131324, 137795, 144410, 151165, 158056, 165079, 172229, 179503, 186894, 194400, 202013, 209728, 217540, 225443, 233431, 241496, 249633, 257834, 267406, 276458, 286328, 296358, 305767, 316074, 326531, 336255, 346965, 357812, 367807, 378880, 390077, 400293, 411686, 423190, 433572, 445239, 457001, 467489, 479378, 491346, 501878, 513934, 526049, 536557, 548720, 560922, 571333, 583539, 591882, 6.0E+5),
        'fluctuating' => array(0,0,4, 13, 32, 65, 112, 178, 276, 393, 540, 745, 967, 1230, 1591, 1957, 2457, 3046, 3732, 4526, 5440, 6482, 7666, 9003, 10506, 12187, 14060, 16140, 18439, 20974, 23760, 26811, 30146, 33780, 37731, 42017, 46656, 50653, 55969, 60505, 66560, 71677, 78533, 84277, 91998, 98415, 107069, 114205, 123863, 131766, 142500, 151222, 163105, 172697, 185807, 196322, 210739, 222231, 238036, 250562, 267840, 281456, 300293, 315059, 335544, 351520, 373744, 390991, 415050, 433631, 459620, 479600, 507617, 529063, 559209, 582187, 614566, 639146, 673863, 700115, 737280, 765275, 804997, 834809, 877201, 908905, 954084, 987754, 1035837, 1071552, 1122660, 1160499, 1214753, 1254796, 1312322, 1354652, 1415577, 1460276, 1524731, 1571884, 1640000),
        'medium-slow' => array(0,0,9, 57, 96, 135, 179, 236, 314, 419, 560, 742, 973, 1261, 1612, 2035, 2535, 3120, 3798, 4575, 5460, 6458, 7577, 8825, 10208, 11735, 13411, 15244, 17242, 19411, 21760, 24294, 27021, 29949, 33084, 36435, 40007, 43808, 47846, 52127, 56660, 61450, 66505, 71833, 77440, 83335, 89523, 96012, 102810, 109923, 117360, 125126, 133229, 141677, 150476, 159635, 169159, 179056, 189334, 199999, 211060, 222522, 234393, 246681, 259392, 272535, 286115, 300140, 314618, 329555, 344960, 360838, 377197, 394045, 411388, 429235, 447591, 466464, 485862, 505791, 526260, 547274, 568841, 590969, 613664, 636935, 660787, 685228, 710266, 735907, 762160, 789030, 816525, 844653, 873420, 902835, 932903, 963632, 995030, 1027103, 1059860),
        'fast' => array(0,0,6, 21, 51, 100, 172, 274, 409, 583, 800, 1064, 1382, 1757, 2195, 2700, 3276, 3930, 4665, 5487, 6400, 7408, 8518, 9733, 11059, 12500, 14060, 15746, 17561, 19511, 21600, 23832, 26214, 28749, 31443, 34300, 37324, 40522, 43897, 47455, 51200, 55136, 59270, 63605, 68147, 72900, 77868, 83058, 88473, 94119, 1.0E+5, 106120, 112486, 119101, 125971, 133100, 140492, 148154, 156089, 164303, 172800, 181584, 190662, 200037, 209715, 219700, 229996, 240610, 251545, 262807, 274400, 286328, 298598, 311213, 324179, 337500, 351180, 365226, 379641, 394431, 409600, 425152, 441094, 457429, 474163, 491300, 508844, 526802, 545177, 563975, 583200, 602856, 622950, 643485, 664467, 685900, 707788, 730138, 752953, 776239, 8.0E+5),
        'slow' => array(0,0,10, 33, 80, 156, 270, 428, 640, 911, 1250, 1663, 2160, 2746, 3430, 4218, 5120, 6141, 7290, 8573, 10000, 11576, 13310, 15208, 17280, 19531, 21970, 24603, 27440, 30486, 33750, 37238, 40960, 44921, 49130, 53593, 58320, 63316, 68590, 74148, 80000, 86151, 92610, 99383, 106480, 113906, 121670, 129778, 138240, 147061, 156250, 165813, 175760, 186096, 196830, 207968, 219520, 231491, 243890, 256723, 270000, 283726, 297910, 312558, 327680, 343281, 359370, 375953, 393040, 410636, 428750, 447388, 466560, 486271, 506530, 527343, 548720, 570666, 593190, 616298, 640000, 664301, 689210, 714733, 740880, 767656, 795070, 823128, 851840, 881211, 911250, 941963, 973360, 1005446, 1038230, 1071718, 1105920, 1140841, 1176490, 1212873, 1250000)
    ];

    public function __construct(Pokemon $user_pokemon, Pokemon $enemy_pokemon) {
        $this->user_pokemon = $user_pokemon;
        $this->enemy_pokemon = $enemy_pokemon;
        return $this;
    }

    //THE FOLLOWING TWO METHODS WERE ADDED INSTEAD OF USING THE POKEMON CLASS

    //set user leveling rate
    //input: string level rate ('fast', 'medium-fast', 'medium-slow', 'slow', 'erratic', 'fluctuating')
    public function set_user_rate(string $leveling_rate){
        $this->user_rate = $leveling_rate;
    }

    //set base enemy exp yield
    //input: int base yield
    public function set_enemy_base_yield(int $base_yield){
        $this->enemy_base_yield = $base_yield;
    }

    //set current generation to determine calculation methods
    //input: integer generation value
    public function set_generation(int $generation){
        $this->generation = $generation;
    }

    //sets level of user pokemon
    //input: integer level
    public function set_user_level(int $level){
        $this->user_level = $level;
    }

    //sets level of enemy pokemon
    //input: integer level
    public function set_enemy_level(int $level){
        $this->enemy_level = $level;
    }

    //sets target level for user pokemon
    //input: integer level
    public function set_target_level(int $level){
        $this->target_level = $level;
    }

    //sets points to next level
    //input: integer level
    public function set_exp_to_next_level(int $points){
        $this->exp_to_next_level = $points;
    }

    //set enemy as a trainer pokemon
    //input: bool, if trainer pokemone then true, else false
    public function set_trainer_battle(bool $is_trainer){
        $this->trainer_battle = $is_trainer;
    }

    //set lucky egg modifier
    //input: bool, if lucky then true, else false
    public function set_lucky_egg(bool $lucky_egg){
        $this->lucky_egg = $lucky_egg;
    }

    //set affection hearts modifier
    //input: bool, if has at least two hearts then true, else false
    public function set_affection_hearts(bool $affection_hearts){
        $this->affection_hearts = $affection_hearts;
    }
    
    //set pass power (gen 5 only)
    //input: integer for number of arrows (negative for downwards arrows)
    public function set_pass_power(int $arrows){
        $this->pass_power_level = $arrows;
    }

    //set o-power (gen 6 only)
    //input: o-power level
    public function set_o_power(int $opower_level){ 
        $this->o_power_level = $opower_level;
    }

    //set rotom power (gen 7 USUM only)
    //input: bool enabled = true, disabled = false
    public function set_rotom_power(bool $rotom){
        $this->rotom_power = $rotom;
    }

    //set experience share deppending on incoming 
    //input: bool enabled = true, disabled = false
    public function set_exp_share(bool $exp_share){
        $this->exp_share = $exp_share;
    }

    //set whether user pokemon was used in battle
    //input: bool enabled = true, disabled = false
    public function set_used_in_battle(bool $used_in_battle){
        $this->used_in_battle = $used_in_battle;
    }

    //set if pokemon was traded
    //input: bool enabled = true, disabled = false
    public function set_traded(bool $traded){
        $this->traded = $traded;
    }

    //set if pokemon is foreign
    //input: bool enabled = true, disabled = false
    public function set_foreign(bool $foreign){
        $this->foreign = $foreign;
    }

    //set if pokemon has passed its evolution level
    //input: bool enabled = true, disabled = false
    public function set_passed_evolution_level(bool $passed){
        $this->passed_evolution_level = $passed;
    }

    //set if exp charm is activated
    //input: bool enabled = true, disabled = false
    public function set_exp_charm(bool $passed){
        $this->exp_charm = $passed;
    }

    //get user leveling rate
    //output: string level rate ('fast', 'medium-fast', 'medium-slow', 'slow', 'erratic', 'fluctuating')
    public function get_user_rate(){
        return $this->user_pokemon->get_growth_rate();
    }

    //get base enemy exp yield
    //output: int base yield
    public function get_enemy_base_yield(){
        return $this->enemy_pokemon->get_base_exp();
    }

    //get the user level at input
    //output: int level
    public function get_user_level(){
        return $this->user_level;
    }

    //determines the modifier for a trainer battle
    //output: a multiplier value 
    protected function get_battle_type_modifier(){
        if($this->trainer_battle && $this->generation < 7){ //! must check if this holds for gen 8, assuming it does for now
            return 1.5;
        }
        else{
            return 1;
        }
    }

    //determines the modifier for lucky egg
    //output: a multiplier value 
    protected function get_lucky_egg_modifier(){
        if($this->lucky_egg && $this->generation != 1){ //no lucky egg in gen 1
            return 1.5;
        }
        else{
            return 1;
        }
    }

    //determines the modifier for affection hearts
    //output: a multiplier value 
    protected function get_affection_hearts_modifier(){
        if($this->affection_hearts && $this->generation >= 6){ //available from gen
            return 1.2;
        }
        else{
            return 1;
        }
    }

    //determines any modifiers from pass power, o-power or rotom power (only in specific games)
    //this is some function wow
    //output: a multiplier value
    protected function get_power_modifier(){

        if($this->pass_power_level != 0 && $this->generation == 5){ //only in gen 5
            switch($this->pass_power_level){ //source: https://bulbapedia.bulbagarden.net/wiki/Entralink#List_of_Pass_Powers
                case -3:
                    return 0.5;
                case -2:
                    return 0.66;
                case -1:
                    return 0.8;
                case 1:
                    return 1.2;
                case 2:
                    return 1.5;
                case 3:
                    return 2;
                default: //not valid
                    return 1;
            }
        }
        elseif($this->o_power_level != 0 && $this->generation == 6){ //only in gen 6
            switch($this->o_power_level){ //source: https://bulbapedia.bulbagarden.net/wiki/O-Power#Exp._Point_Power
                case 1:
                    return 1.2;
                case 2:
                    return 1.5;
                case 3:
                case 'S': //should not happen
                case 'MAX': //should not happen
                    return 2;
                default:
                    return 1;
            }
        }
        elseif($this->rotom_power && $this->generation == 7){ //only in gen 7
            return 1.5;
        }
        else{
            return 1;
        }

    }

    //determines the modifier for the exp share
    //output: a multiplier value 
    protected function get_exp_share_modifier(){ //! Kind of simplified to have less inputs for more info check: https://bulbapedia.bulbagarden.net/wiki/Experience#Gain_formula
        if($this->exp_share && !$this->used_in_battle){
            return 0.5;
        }
        else{
            return 1;
        }
    }

    //determines the modifier if a pokemon was traded and/or foreign
    //output: a multiplier value 
    protected function get_traded_modifier(){
        
        $output = 1;

        if($this->traded){
            $output = 1.5;
        }
        if($this->foreign){ //0.2 is added regardless if it was traded or not
            $output += 0.2;
        }

        return $output;
    }

    //determines the modifier if the pokemon us already passed it evolution level
    //output: a multiplier value 
    protected function get_unevolved_modifier(){
        if($this->passed_evolution_level && $this->generation >= 6){ //available from gen 6
            return 1.2;
        }
        else{
            return 1;
        }
    }

    //determines the modifier if the exp charm is activated
    //output: a multiplier value 
    protected function get_exp_charm_modifier(){
        if($this->exp_charm && $this->generation >= 8){ //available from gen 8
            return 1.5;
        }
        else{
            return 1;
        }
    }

    //calculates yield upon defeat of one pokemon, notice generations 5, 7 and 8 use a different formula to calculate yield
    //formulas can be found here: https://bulbapedia.bulbagarden.net/wiki/Experience#Gain_formula
    //input: user level (needed for relative calculations later)
    //output: integer exp points
    public function calculate_exp_yield(int $current_level){

        $battle_type_modifier = $this->get_battle_type_modifier(); //modifies exp yield if trainer battle (before gen 7)
        $base_yield = $this->get_enemy_base_yield(); //determines the base exp yield for a pokemon
        $lucky_egg_modifier = $this->get_lucky_egg_modifier();
        $affection_modifier = $this->get_affection_hearts_modifier(); //modifies exp yield with at least two hearts
        $enemy_level = $this->enemy_level;
        $power_modifier = $this->get_power_modifier();
        $exp_share_modifier = $this->get_exp_share_modifier();
        $traded_modifier = $this->get_traded_modifier(); //includes foreign modified if any
        $unevolved_modifier = $this->get_unevolved_modifier();  
        $exp_charm_modifier = $this->get_exp_charm_modifier();

        $yield = 0;
        if($current_level >= 100){
            return $yield;
        }

        if($this->generation != 5 && $this->generation < 7){ //uses flat formula
            $yield = ($battle_type_modifier * $traded_modifier * $base_yield * $lucky_egg_modifier * $enemy_level * $power_modifier * $affection_modifier * $unevolved_modifier* $exp_share_modifier) / 7;
        }
        elseif($this->generation == 5){  //uses scaled formula
            $yield = ((($battle_type_modifier * $base_yield * $enemy_level * $exp_share_modifier)/5) *  ((2*$enemy_level + 10)/($enemy_level + $current_level + 10))**2.5 + 1) * $traded_modifier * $lucky_egg_modifier * $affection_modifier;
        }
        elseif($this->generation >= 7){  //uses scaled formula
            $yield = ((($battle_type_modifier * $base_yield * $enemy_level * $exp_share_modifier)/5) *  ((2*$enemy_level + 10)/($enemy_level + $current_level + 10))**2.5 +1) * $traded_modifier * $lucky_egg_modifier * $affection_modifier * $exp_charm_modifier;
        }

        return floor($yield);
    }

    //calculates the amount of exp that is required to reach target level (takes into account experience gained on current level, if any)
    //output: int required exp
    public function get_exp_to_target_level(){

        $leveling_rate = $this->get_user_rate();

        $output = 0;
        
        if($this->exp_to_next_level != null){
            $output = $this->exp_to_next_level;
        }
        else{
            $output = self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$this->user_level + 1] - self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$this->user_level]; //get exp to the next level
        }
        

        $level = $this->user_level + 1; //start at next level 

        while($level < $this->target_level){ //loop through each additional level until target is reached
            $output += (self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$level + 1] - self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$level]); //get exp to the next level
            $level++;
        }

        return (int)$output;
    }

    //calculates the average amount of enemy pokemon the user has to defeat to reach a target level
    //output: int average number for enemies to beat (rounded up)
    public function calculate_enemies_to_defeat(){

        $leveling_rate = $this->get_user_rate();
        $goal_exp = $this->get_exp_to_target_level();

        $exp_gained = 0; //aggregate exp since function call
        $since_last_level = 0;
        $enemy_count = 0;
        $current_level = $this->user_level;

        if($current_level == 100){
            return 0;
        }

        //loop through 
        while($exp_gained < $goal_exp){
            $exp_gained += $this->calculate_exp_yield($current_level);
            $enemy_count++;

            //if recieved enough exp, increment to second level as some experience for the intial level has already been gained
            if($current_level == $this->user_level && $this->exp_to_next_level != null){
                if($since_last_level >= $this->exp_to_next_level){
                    $current_level++;
                    $since_last_level -= $this->exp_to_next_level;
                }
            }

            //loop to level up as many levels as necessary if enough exp has been reached from winning this fight
            //should usually only iterate once but may iterate more
            while($since_last_level >= self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$current_level + 1] - self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$current_level]){ //need full amount for next level 
                $current_level++;
                $since_last_level -= self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$current_level + 1] - self::EXP_REQUIRED_FOR_LEVEL[$leveling_rate][$current_level];

                if($current_level == 100){
                    return ceil($enemy_count);
                }
            }

        }

        return ceil($enemy_count);
    }

    //returns original target level (for front-end)
    //output: int target level
    public function get_target_level(){
        return $this->target_level;
    }

}