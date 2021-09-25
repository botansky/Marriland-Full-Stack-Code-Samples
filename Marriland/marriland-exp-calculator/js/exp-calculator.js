$('#exp-generation select').on('change', function() {
    const generation = Number($(this).val());
    $('#exp-calculator').removeClass('gen-1 gen-2 gen-3 gen-4 gen-5 gen-6 gen-7 gen-8').addClass('gen-' + String(generation));
    const exp_share = $('#exp-exp-share').prop('checked');
    if(exp_share){
        $('#exp-used-field').show();
    }
    else{
        $('#exp-used-field').hide();
    }
});

$('#exp-range').on('change', function() {
    const range = $('#exp-range').prop('checked');
    $('#exp-range-field').hide();

    if(range){
        $('#exp-enemy-level-field').hide();
        $('#exp-range-field').show();
    }
    else{
        $('#exp-enemy-level-field').show();
        $('#exp-range-field').hide();
    }
});

$('#exp-exp-share').on('change', function() {
    const exp_share = $('#exp-exp-share').prop('checked');
    if(exp_share){
        $('.exp-share-on').show();
    }
    else{
        $('.exp-share-on').hide();
    }
});

$('#exp-user-level-toggle').on('change', function() {
    const user_input = $('#exp-user-level-toggle').prop('checked');
    if(user_input){
        $('.user-input').show();
    }
    else{
        $('.user-input').hide();
    }
});


$('#exp-form').submit(function(e) {
    e.preventDefault(); // prevents the form from actually submitting

    $.get(wp.ajax_url, {
        action: 'marriland_exp_calculator',

        //user init
        generation: Number($('#exp-generation select').val()),
        user_name: $('#exp-user-name input').val(),
        user_level_toggle: $('#exp-user-level-toggle').prop('checked'),
        user_level: $('#exp-user-level').val(),
        target_level: $('#exp-target-level').val(),

        //user experience modifiers
        traded: $('#exp-traded').prop('checked'),
        foreign: $('#exp-foreign').prop('checked'),
        lucky_egg: $('#exp-lucky-egg').prop('checked'),
        exp_share: $('#exp-exp-share').prop('checked'),
        used: $('#exp-used').prop('checked'),
        pass_power: $('#exp-pass-power').val(),
        o_power: $('#exp-o-power').val(),
        rotom_power: $('#exp-rotom-power').prop('checked'),
        exceeded_evolution: $('#exp-exceed-evo').prop('checked'),
        affection: $('#exp-affection').val(),
        exp_charm: $('#exp-exp-charm').prop('checked'),

        //enemy init
        enemy_name: $('#exp-enemy-name').val(),
        enemy_level: $('#exp-enemy-level').val(),
        enemy_range: $('#exp-range').prop('checked'),
        enemy_min: $('#exp-enemy-min').val(),
        enemy_max: $('#exp-enemy-max').val(),

        //ememy modifiers
        trainer: $('#exp-trainer').prop('checked'),

        //all inputs from main.tpl.html above

    }).done(function(response) {

        $('#exp-user-image').html(response.user_image);
        $('#exp-enemy-image').html(response.enemy_image);
        $('.exp-user-name-out').html(response.user_name);
        $('.exp-enemy-name-out').html(response.enemy_name);
        $('#exp-single-win').html(response.single_win);
        $('#exp-min-single-win').html(response.min_single_win);
        $('#exp-max-single-win').html(response.max_single_win);
        $('#exp-to-target').html(response.exp_to_target);
        $('#exp-enemy-count').html(response.enemy_count);
        $('#exp-min-enemy-count').html(response.min_enemy_count);
        $('#exp-max-enemy-count').html(response.max_enemy_count);
        $('.exp-target-level-out').html(response.target_level);

        if(response.calculated){
            $('#exp-output-graphics').show();
            const range = $('#exp-range').prop('checked');
            if(!range){
                $('#exp-normal-output-text').show();
                $('#exp-range-output-text').hide(); 
            }
            else{
                $('#exp-normal-output-text').hide();
                $('#exp-range-output-text').show(); 
            }

            const use_level = $('#exp-user-level-toggle').prop('checked');
            const generation = Number($('#exp-generation select').val());
            if(!use_level && generation < 7 && generation != 5 ){
                $('.exp-user-level-output').hide();            
            }
            else{
                $('.exp-user-level-output').show();            
            }
        }
    });
})