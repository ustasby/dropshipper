$(document).ready(function() {
    $('.order-status__verify').on('click', function() {
        $('.order-status__result').html('');
        $('input[name="order-number"]').val('');
        $('.order-status__overlay').addClass('show-modal');
        $('.order-status__window').addClass('show-modal');
    });

    $('.order-status__overlay').on('click', function() {
        $('.order-status__window').removeClass('show-modal');
        $('.order-status__result-window').removeClass('show-modal');
        $('.order-status__overlay').removeClass('show-modal');
    });

    $('.order-status__close').on('click', function() {
        $('.order-status__window').removeClass('show-modal');
        $('.order-status__result-window').removeClass('show-modal');
        $('.order-status__overlay').removeClass('show-modal');
    });

    $('.order-status__back').on('click', function() {
        $('.order-status__result').html('');
        $('input[name="order-number"]').val('');
        $('.order-status__result-window').removeClass('show-modal');
        $('.order-status__window').addClass('show-modal');
    });

    $('form[name="order-status"]').on('submit', function() {
        var $form = $(this);
        $('.order-status__window li.loading').show();
        $.ajax({
            type: $form.attr('method'),
            dataType: 'json',
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function(response) {
                $('.order-status__window').removeClass('show-modal');
                $('.order-status__result-window').addClass('show-modal');
                $('.order-status__result').html(response.html);
                $('.order-status__window li.loading').hide();
            }
        });
        return false;
    });
});