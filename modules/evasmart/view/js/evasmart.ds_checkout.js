$(function() {
    $('#rs-ds-cart-form').on('submit', function () {
        var result = true;
        $(this).find(".js-ds-cost").each(function (index, value) {
            //console.log($(value).val());
            if (1 > parseInt($(value).val())) {
                result = false;
            }
        });
        if (!result) {
            $('.js-ds-error').html('Проставьте все цены для покупателей');
            $('.js-ds-error').removeClass('hidden');
            return false;
        }
        $('.js-ds-error').addClass('hidden');
    });




});
