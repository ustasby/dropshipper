/**
 * Скрипт смотрит когда меняется текущий адрес страницы и подставляет в адрес кнопки SEO контроль верное значение
 */
$(function () {
    if ($("#debug-top-block").length){ //Только если видна админ панель
        var oldLocation = location.href;
        setInterval(function() {
            if(location.href != oldLocation) {
                //Поменяем адрес для запроса у кнопки в админ панели.
                var new_location = encodeURIComponent(document.location.pathname + document.location.search);
                var old_href     = $("#seocontrol-top-button").attr('href');
                var new_href     = old_href.replace(/uri=(.*)&/iu, "uri=" + new_location + "\&");
                $("#seocontrol-top-button").attr('href', new_href);
                oldLocation = location.href
            }
        }, 700); // check every second
    }
});