<?php
namespace Marriland\Team_Builder;

/*  MATAN: Below is the entire class for sorting Team Builder suggestions. I know we both have the same comment colour extension so I used it to our advantage:
           Any text in RED comments means DEBUGGING ONLY code segments or instructions (there is a whole debugging template after the class, it's not neat but very simple)
           Any text in BLUE comments means clarification/future suggestions on SUBJECTIVE decisions
           Any text in GREEN comments means that there is an equations that is considered ADJUSTABLE OR NEEDS BALANCING
*/

class TB_Suggestions{

    protected $generation = 8;
    protected $current_team = [];
    protected $filtered_possible = [];

    //?the following priorities are the default/recommended priorities and can be changed with the change_priorities() function or by default within here
    protected $priorities = [
        'weakness_resistance' => 5,         //prioritizes pokemon that resist types that the current team is weak to 
        'type_matching' => 3,               //prioritizes pokemon that have types that do not overlap with any pokemon on the current team
        'base_stat_totals' => 3,            //prioritizes pokemon with a higher base stat total
        'off_def_balance' => 1,             //prioritizes pokemon that help balance the team in terms of offensive and defensive stats
        'phys_spec_balance' => 1,           //prioritizes pokemon that help balance the team in terms of physical and special stats
    ];

    const NEUTRAL_WR_TABLE = [
        'normal' => 100,
        'fire' => 100,
        'water' => 100,
        'electric' => 100,
        'grass' => 100,
        'ice' => 100,
        'fighting' => 100,
        'poison' => 100,
        'ground' => 100,
        'flying' => 100,
        'psychic' => 100,
        'bug' => 100,
        'rock' => 100,
        'ghost' => 100,
        'dragon' => 100,
        // 'dark' => 100,
        // 'steel' => 100,
        // 'fairy' => 100
    ];


    public function __construct(array $user_team, array $filtered_possible, int $generation = 0) {
        if ($generation === 0) {
            //! Enable following line once connected to the Marriland workspace
            // $generation = Common::$current_generation;
            $generation = 8;
        }

        $this->generation = $generation;
        $this->current_team = $user_team;        
        $this->filtered_possible = $filtered_possible;        

        return $this;
    }
    
    
    //TEAM ANALYSIS FUNCTIONS

    /*  Takes in the user team and returns an array of types used, as well as the number of times they occur.
        Inputs: A user team array containing the types data for each Pokemon
        Outputs: A key => value array with the types and their frequency */
    protected function calculate_team_types(array $team){
        $types_used = [];
        foreach($team as $pokemon){  //we want to add the type of each pokemon to the array
            foreach($pokemon['type'] as $type){ //add each type individually
                array_push($types_used, $type);
            }
        }
        $types_used = array_count_values($types_used);  //return a count indexed to each type 
        arsort($types_used, SORT_NUMERIC);  //sort by the total weakness

        return $types_used;
    }
    
    /*  Takes in the user team and returns an array of weaknesses and resistances, as well as the percent value.
        Inputs: A user team array containing the weakness/resistance data for each Pokemon
        Outputs: A key => value array with the types and weakness percentage */
    protected function calculate_team_wr(array $team){
        $team_size = count($team); //get count of number of team member to calculate aggregate weaknesses/resistances
        $team_wr = []; //this will be the output array

        //the following cases below check for added types in each generation
        if($this->generation > 5){
            $added_wr_types = ['dark' => 100, 'steel' => 100, 'fairy' => 100];
            $team_wr = array_merge($this::NEUTRAL_WR_TABLE, $added_wr_types);
        }
        else if($this->generation > 1){
            $added_wr_types = ['dark' => 100, 'steel' => 100];
            $team_wr = array_merge($this::NEUTRAL_WR_TABLE, $added_wr_types);
        }
        else{
            $team_wr = $this::NEUTRAL_WR_TABLE;
        }

        //loop through each pokemon's wr_table and adjust the team wr table accordingly
        $team_count = 0; //to help track position in foreach loop
        foreach($team as $pokemon){

            $team_count += 1;

            foreach($pokemon['wr_table'] as $type => $value){
                //skip over any neutral type and recalculate average
                if($value != 100){
                    $team_wr[$type] += ($value - 100)/$team_size; //adjusts aggregate w/r
                }

                //? This currently does not provide data for neutral wr and resistances, could be changed to an array of all types
                //? sorted by a merge sort of O(n*log(n)) time complexity to provide slightly better results, however I personally 
                //? believe that the extra performance is not worth it.
                //remove the w/r type results if a neutral value after all pokemon have been added into the calculation
                if($team_count === $team_size && $team_wr[$type] <= 100){       
                    unset($team_wr[$type]);
                }
            }
        }

        return $team_wr;
    }

    /*  Takes in the user team and returns an array of types and weakness count
    Inputs: A user team array containing the weakness/resistance data for each Pokemon
    Outputs: A key => value array with the types and their frequency (if something is 4x weak it will be counted as 2) */
    protected function calculate_team_weakness_severity(array $team){
        $team_weaknesses = []; //this will be the output array

        foreach($team as $pokemon){  //we want to add the weakness of each pokemon to the array
            foreach($pokemon['wr_table'] as $type => $value){ //add each weakness individually
                if($value >= 200){
                    array_push($team_weaknesses, $type);
                }
            }
        }
        $team_weaknesses = array_count_values($team_weaknesses);  //return a count indexed to each weakness 
        arsort($team_weaknesses, SORT_NUMERIC);  //sort by the total weakness

        return $team_weaknesses;
    }

    /*  Takes in the user team and returns an array of base stats and their averages across the team.
        Inputs: A user team array containing the complete base stat data for each Pokemon
        Outputs: A key => value array with the stat category and its average value */
    protected function calculate_team_stats(array $team){
        $team_size = count($team); //get count of number of team member to calculate aggregate stat averages
        $team_stats = []; //this will be the output array
        
        $team_count = 0; //to help track position in foreach loop
        foreach($team as $pokemon){  //we are going to loop through each team member and calculate the average base stats
            $team_count += 1; //next pokemon

            if($team_count == 1){ //we want to use the first pokemon's base stats instead of initializing a base stat array to save performance
                $team_stats = $pokemon['base_stats'];
            }
            else{
                foreach($pokemon['base_stats'] as $stat => $value){
                    $team_stats[$stat] += $value; //add on each value for each of the next pokemon
                    if($team_count == $team_size){
                        $team_stats[$stat] /= $team_count; //divide on the last pokemon of the team to skip another iteration
                    }
                }
            }
        }

        return $team_stats;
    }


    //INDEX GENERATION FUNCTIONS:

    /* Allows to change the priorities of the sorting algorithm with values ranging from 0-5 
       Inputs: An int value (modulus 6) for each priority in this extension */
    public function set_priorites(int $weakness_resistance, int $type_matching, int $base_stat_totals, int $off_def_balance, int $phys_spec_balance){
        //everything gets modulus 6 to ensure inputs are valid
        $this->priorities = [
            'weakness_resistance' => $weakness_resistance % 6,           //prioritizes pokemon that resist types that the current team is weak to 
            'type_matching' => $type_matching % 6,                       //prioritizes pokemon that have types that do not overlap with any pokemon on the current team
            'base_stat_totals' => $base_stat_totals % 6,                 //prioritizes pokemon with a higher base stat total
            'off_def_balance' => $off_def_balance % 6,                   //prioritizes pokemon that help balance the team in terms of offensive and defensive stats
            'phys_spec_balance' => $phys_spec_balance % 6,               //prioritizes pokemon that help balance the team in terms of physical and special stats
        ];
    }
    
    /*  This function is the main function of this extension that sorts the input array based on the requirements of your team.
        Inputs: None (set up by constructor)
        Outputs: An array of each pokemon slug and its rating value as an integer (0 - 100) */
    public function generate_filtered_suggestions(){
        $suggestions = [];  //we will store out suggestions here so we can sort them at the end, this will also be the return array

        $team_type_count = $this->calculate_team_types($this->current_team);
        $team_wr = $this->calculate_team_wr($this->current_team);
        $team_weakness_count = $this->calculate_team_weakness_severity($this->current_team);
        $team_stats = $this->calculate_team_stats($this->current_team);

        //start by looping through all potential pokemon
        foreach($this->filtered_possible as $pokemon){
            $score = 50;     //acts as default score for ranking
            $disqualified = false;
        
            //start by looking at the team weaknesses to score based on potential resistances
            foreach($team_wr as $team_weakness => $wr_value){

                //high severity rating boundaries
                switch(count($this->current_team)){
                    case 1:
                        $severity_boundary = 1;
                        break;
                    case 2:
                        $severity_boundary = 2;
                        break;
                    default:  //team size 3 - 6 have equivalent boundaries
                        $severity_boundary = 3;
                        break;
                }

                //team average wr scoring
                if($pokemon['wr_table'][$team_weakness] < 100){
                    //!echo $team_weakness, ' team: ',  $wr_value, ' pokemon: ', $pokemon['slug'], ' ', $pokemon['wr_table'][$team_weakness], "\n"; //ENABLE FOR DEBUGGING
                    //improve score by the product of the decimal weakness value of the team, the priority value and the offset of the 
                    //decimal value of the resistance of this pokemon by a weighted constant
                    $score += (($wr_value / 100) * $this->priorities['weakness_resistance'] * (2 - ($pokemon['wr_table'][$team_weakness] / 100)));   //* weighting not yet balanced
                }
                else if($pokemon['wr_table'][$team_weakness] > 100){
                    //!echo $team_weakness, ' team: ',  $wr_value, ' pokemon: ', $pokemon['slug'], ' ', $pokemon['wr_table'][$team_weakness], "\n"; //ENABLE FOR DEBUGGING
                    //improve score by the product of the decimal weakness value of the team, the priority value and the offset of by a weighted 
                    //constant by the decimal value of the resistance of this pokemon
                    $score -= (($wr_value / 100) * $this->priorities['weakness_resistance'] * (($pokemon['wr_table'][$team_weakness] / 100) - 2));   //* weighting not yet balanced

                    //take a penalty if passed the severity boundary
                    if($team_weakness_count[$team_weakness] + 1 >= $severity_boundary){
                        $disqualified = true;
                        break;
                    }
                }
            }

            if($disqualified == true){
                continue;
            }

            //now look to avoid any overlapping types currently on the team
            foreach($team_type_count as $type => $frequency){
                //!echo 'type used in team: ', $type, ' frequency used: ', $frequency, ' pokemon: ', $pokemon['slug'], "\n"; //ENABLE FOR DEBUGGING
                if(in_array((string)$type, $pokemon['type'])){
                    $score -= (2**$frequency * $this->priorities['type_matching']);    //* weighting not yet balanced
                }
            }

            $base_stat_total = $pokemon['base_stats']['total'];
            $offensive_rating = 0;       //average of suggestion's offensive stats
            $team_offense_rating = 0;    //average of team's offensive stats
            $defensive_rating = 0;       //average of suggestion's defensive stats
            $team_defensive_rating = 0;  //average of team's defensive stats
            $team_attack = 0;
            $team_spatk = 0;
            //now loop through the statistical distribution of the current team
            foreach($team_stats as $stat => $value){
                //we want to update the values from above in one iteration of the array only
                //notice that we are calculating elements from both the current team stat totals and the suggestion's stat total
                if($this->generation == 1){  //generation 1 uses only special instead of spatk and spdef
                    if($stat == 'attack'){
                        $offensive_rating += ($pokemon['base_stats'][$stat] / 2);
                        $team_offense_rating += ($value / 2);
                        $team_attack = $value;
                    }
                    else if($stat == 'defense'){
                        $defensive_rating += ($pokemon['base_stats'][$stat] / 2);
                        $team_defensive_rating += ($value / 2);
                    }
                    else if($stat == 'special'){
                        $offensive_rating += ($pokemon['base_stats'][$stat] / 2);
                        $team_offense_rating += ($value / 2);
                        $defensive_rating += ($pokemon['base_stats'][$stat] / 2);
                        $team_defensive_rating += ($value / 2);
                        $team_spatk = $value;
                    }
                }
                else{
                    if($stat == 'attack'){
                        $offensive_rating += ($pokemon['base_stats'][$stat] / 2);
                        $team_offense_rating += ($value / 2);
                        $team_attack = $value;
                    }
                    else if($stat == 'defense' || $stat == 'spdef'){
                        $defensive_rating += ($pokemon['base_stats'][$stat] / 2);
                        $team_defensive_rating += ($value / 2);
                    }
                    else if($stat == 'spatk'){
                        $offensive_rating += ($pokemon['base_stats'][$stat] / 2);
                        $team_offense_rating += ($value / 2);
                        $team_spatk = $value;
                    }
                }
            }

            //remove or add score based on the difference with a balanced median fixed base stat value
            //! echo 'pokemon: ', $pokemon['slug'], ' BST: ', $base_stat_total, "\n";  //ENABLE FOR DEBUGGING
            $score += (int)($this->priorities['base_stat_totals'] * ((($base_stat_total) - 417) / 7));  //* weighting not yet balanced (417 is the average BST for a final form in gen 6, I couldn't find anything more recent)

            //remove or add score based on how well this pokemon and the team is balanced when comparing offensive and defensive stats
            if($team_offense_rating - $team_defensive_rating > 10){
                $score += (int)($this->priorities['off_def_balance'] * (($defensive_rating - $offensive_rating) - ($team_offense_rating - $team_defensive_rating) )/ 100);    //* weighting not yet balanced
            }
            else if($team_defensive_rating - $team_offense_rating > 10){
                $score += (int)($this->priorities['off_def_balance'] * (($offensive_rating - $defensive_rating) - ($team_defensive_rating - $team_offense_rating) )/ 100);    //* weighting not yet balanced
            }

            //do the same thing but this time comparing attack and special attack
            if($team_attack - $team_spatk > 10){
                $score += (int)($this->priorities['phys_spec_balance'] * (($pokemon['base_stats']['spatk'] - $pokemon['base_stats']['attack']) - ($team_attack - $team_spatk) ) / 100);    //* weighting not yet balanced
            }
            else if($team_spatk - $team_attack > 10){
                $score += (int)($this->priorities['phys_spec_balance'] * (($pokemon['base_stats']['attack'] - $pokemon['base_stats']['spatk']) - ($team_spatk - $team_attack) )/ 100);    //* weighting not yet balanced
            }

            //now we add this pokemon to the the suggestions array with its slug and scoring
            array_push($suggestions, ['slug' => $pokemon['slug'], 'score' => round($score)]);
        }

        //sort by the 'score' column of the array
        $score_column = array_column($suggestions, 'score');
        array_multisort($score_column, SORT_DESC, $suggestions);
        return $suggestions;
    }


}