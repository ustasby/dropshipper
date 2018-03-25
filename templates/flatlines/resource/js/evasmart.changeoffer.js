$(function() {

    $("#color_mat").on('change', function () {
        var color_mat = $(this).val();
        if (color_mat == "Соты") {
            $("#color_sota").show();
            $("#color_romb").hide();
        } else {
            $("#color_sota").hide();
            $("#color_romb").show();
        }
        //console.log(color_mat.data("change"));
    });

    $('.rs-to-cart').on('click', function () {

        var res = '/cart/?add=' + $('#updateProduct').data('id');
        var mat = $("#color_mat option:selected").val();
        if (mat == undefined) {
            return true;
        }

        res = res + '&color_mat=' + mat;
        if (mat == 'Соты') {
            res = res + '&color=' + $("#color_sota option:selected").val();
        } else {
            res = res + '&color=' + $("#color_romb option:selected").val();
        }
        res = res + '&kant=' + $("#color_kant option:selected").val();
        console.log($("#color_mat option:selected").val());

        $(this).data('url', res);

        return true;
    });

});
