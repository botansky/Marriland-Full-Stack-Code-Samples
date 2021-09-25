function next_iv_level(level) {
    if (level == 101) {
        return '<strong class="iv-range-confirmed">Confirmed!</strong>';
    } else if (level == -1) {
        return '<strong class="iv-range-error">No possible IVs!</strong> Double-check your stats, Nature, and make sure the EVs are correct.';
    }
    return `Check stats again at <strong class="iv-suggested-level">level ${level}</strong>`;
}

function toggle_ev_rows() {
    const is_checked = $('#iv-input-evs').prop('checked');
    if (is_checked) {
        $('#iv-level-table tbody tr.iv-ev-row').show();
    } else {
        $('#iv-level-table tbody tr.iv-ev-row').hide();
    }
}

$('#iv-form').submit(function(e) {
    e.preventDefault(); // prevents the form from actually submitting

    var levels = [];
    $('#iv-level-table tbody tr.iv-stat-row').each(function() {
        let current_row = $(this).attr('data-row');
        let $ev_row = $(this).siblings('tr[data-row=' + current_row + ']');
        let this_level = {
            level: Number($(this).find('.iv-input-level').val()),
            stats: {
                hp: Number($(this).find('.iv-input-hp').val()),
                attack: Number($(this).find('.iv-input-attack').val()),
                defense: Number($(this).find('.iv-input-defense').val()),
                spatk: Number($(this).find('.iv-input-spatk').val()),
                spdef: Number($(this).find('.iv-input-spdef').val()),
                speed: Number($(this).find('.iv-input-speed').val())
            },
            evs: {}
        };
        if ($ev_row.find('.iv-input-ev-hp').val().length > 0) {
            this_level.evs.hp = Number($ev_row.find('.iv-input-ev-hp').val());
        }
        if ($ev_row.find('.iv-input-ev-attack').val().length > 0) {
            this_level.evs.attack = Number($ev_row.find('.iv-input-ev-attack').val());
        }
        if ($ev_row.find('.iv-input-ev-defense').val().length > 0) {
            this_level.evs.defense = Number($ev_row.find('.iv-input-ev-defense').val());
        }
        if ($ev_row.find('.iv-input-ev-spatk').val().length > 0) {
            this_level.evs.spatk = Number($ev_row.find('.iv-input-ev-spatk').val());
        }
        if ($ev_row.find('.iv-input-ev-spdef').val().length > 0) {
            this_level.evs.spdef = Number($ev_row.find('.iv-input-ev-spdef').val());
        }
        if ($ev_row.find('.iv-input-ev-speed').val().length > 0) {
            this_level.evs.speed = Number($ev_row.find('.iv-input-ev-speed').val());
        }

        levels.push(this_level);
    });

    $.get(wp.ajax_url, {
        action: 'marriland_iv_calculator',

        generation: Number($('#iv-generation select').val()),
        name: $('#iv-pokemon input').val(),
        nature: $('#iv-nature select').val(),
        characteristic: $('#iv-characteristic select').val(),
        hidden_power: $('#iv-hidden-power select').val(),

        levels: levels

        /*
        level: $last_row.find('.iv-input-level').val(),
        hp: $last_row.find('.iv-input-hp').val(),
        attack: $last_row.find('.iv-input-attack').val(),
        defense: $last_row.find('.iv-input-defense').val(),
        spatk: $last_row.find('.iv-input-spatk').val(),
        spdef: $last_row.find('.iv-input-spdef').val(),
        speed: $last_row.find('.iv-input-speed').val(),
        hp_ev: $last_row.find('.iv-input-ev-hp').val(),
        attack_ev: $last_row.find('.iv-input-ev-attack').val(),
        defense_ev: $last_row.find('.iv-input-ev-defense').val(),
        spatk_ev: $last_row.find('.iv-input-ev-spatk').val(),
        spdef_ev: $last_row.find('.iv-input-ev-spdef').val(),
        speed_ev: $last_row.find('.iv-input-ev-speed').val(),
        */

    }).done(function(response) {

        $('#iv-results').show();

        $('#iv-result-hp .iv-possible-values').html(response.iv_array.hp.join(', '));
        $('#iv-result-attack .iv-possible-values').html(response.iv_array.attack.join(', '));
        $('#iv-result-defense .iv-possible-values').html(response.iv_array.defense.join(', '));
        $('#iv-result-spatk .iv-possible-values').html(response.iv_array.spatk.join(', '));
        $('#iv-result-spdef .iv-possible-values').html(response.iv_array.spdef.join(', '));
        $('#iv-result-speed .iv-possible-values').html(response.iv_array.speed.join(', '));

        $('#iv-result-hp .iv-check-at-level').html(next_iv_level(response.next_level_hp));
        $('#iv-result-attack .iv-check-at-level').html(next_iv_level(response.next_level_attack));
        $('#iv-result-defense .iv-check-at-level').html(next_iv_level(response.next_level_defense));
        $('#iv-result-spatk .iv-check-at-level').html(next_iv_level(response.next_level_spatk));
        $('#iv-result-spdef .iv-check-at-level').html(next_iv_level(response.next_level_spdef));
        $('#iv-result-speed .iv-check-at-level').html(next_iv_level(response.next_level_speed));
    });
});

$('#iv-generation select').change(function() {
    const generation = Number($(this).val());
    $('#iv-characteristic').show();
    $('#iv-hidden-power').show();
    switch(generation) {
        case 8:
            $('#iv-hidden-power').hide();
            $('#iv-hidden-power select').val('none');
            break;
        case 3:
            $('#iv-characteristic').hide();
            $('#iv-characteristic select').val('none');
            break;
    }
});

$('#iv-add-row').click(function(e) {
    if ($('#iv-level-table tbody').children().length >= 30) {
        alert("There's currently a limit of 15 rows for calculating! Please start over with a new calculation at your most recent level rather than adding new rows.");
        return;
    }
    let last_row_id = Number($('#iv-level-table tbody tr').last().attr('data-row'));
    let $new_row = $('#iv-input-level-row').clone();
    $('#iv-level-table tbody')
    .append(
        $new_row
        .html()
        .replace(/data\-row\=\"\"/g, 'data-row="' + Number(last_row_id + 1) + '"')
    );
    $new_row.remove();
    toggle_ev_rows();
});

$('#iv-input-evs').change(toggle_ev_rows);