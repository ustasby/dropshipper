(function( $ ){
   $.deliveryWidjetCreator = function( method ) {
        //Найдём ближайший input c выбором
        var closestRadio = $('input[name="delivery"]:eq(0)');
        var form         = closestRadio.closest('form'); //найдем форму

        var defaults = {
           additionalHTMLBlock : '.deliveryWidjet',                //Контейнер куда будет вставлятся информация дополнтительного функционала
           deliveryExtra       : '[name="delivery_extra[value]"]', //Скрытое поле с экстра информацией
           deliveryExtraCode   : '[name="delivery_extra[code]"]', //Скрытое поле с экстра информацией
           select              : '.pickpointsSelect',              //Выпадающий список с забора дополнтительного функционала
           additionalInfo      : '.pickpointsAdditionalInfo',            //Дополнительная информация дополнтительного функционала
           openMapButton       : '.pickpointsOpenMap',             //Кнопка открытия карты
           map                 : '.pickpointsMap',                 //Карта с выбором пукта забора
           yandexReady         : false                             //Флаг того, что яндекс загрузился
        },

        $this = form,
        data  = $this.data('deliveryInfo');

        if (!$this.length) return;
        if (!data) { //Инициализация
            data = { options: defaults };
            $this.data('deliveryInfo', data);
        }

        //public
        var methods = {
            /**
            * Инициализация плагина
            *
            * @param initoptions - введённые пераметра для записи или перезаписи
            */
            init: function(initoptions)
            {
                data.options = $.extend(data.options, initoptions);

                bindEvents();
                changeDelivery();

                //Если контент обновился (для заказа на одной странице)
                $('body').on('new-content', function(){
                    bindEvents();
                    changeDelivery();
                });
            }
        };

        //private

        /**
         * Смена доставки
         */
        var changeDelivery = function (){

            //Отключим поля внутри лишних блоков
            $(data.options.additionalHTMLBlock).each(function(){
                $("select", $(this)).prop('disabled', true);
                $("input", $(this)).prop('disabled', true);
                $("button", $(this)).prop('disabled', true);
                $("textarea", $(this)).prop('disabled', true);
            });

            //Откроем поля только для определённой доставки
            var current = getCurrentAdditionalHTMLBlock();
            $("select", current).prop('disabled', false);
            $("input", current).prop('disabled', false);
            $("button", current).prop('disabled', false);
            $("textarea", current).prop('disabled', false);
            $(data.options.select, current).trigger('change');
        },

        /**
         * Возвращает текущий оборачивающий блок
         */
        getCurrentAdditionalHTMLBlock = function (){
            var delivery_id = $('input[name="delivery"]:checked').val();
            return $(data.options.additionalHTMLBlock + "[data-delivery-id='" + delivery_id + "']");
        },

        /**
        * Смена адреса пункта выдачи в выпадающем списке
        */
        changeDeliveryPickpoints = function()
        {
            var context  = $(this).closest(data.options.additionalHTMLBlock);
            var selected = $("option:selected", $(this)); //Тещий выбранный вариант
            var val      = JSON.parse(selected.val());
            if (val.info){
                $(data.options.additionalInfo, context).html(val.info);
                $(data.options.deliveryExtraCode, context).val(val.code);
            }
        },

        /**
         * Открытие карты с пунктами выдачи товара
         */
        openMap = function ()
        {
            var parent = $(this).closest(data.options.additionalHTMLBlock); //Оборачивающий контейнер
            var mapDiv = $(data.options.map, parent);            //Карта
            var select = $(data.options.select, parent);         //Выпадающий список

            //Соберём доступные координаты
            var addresses = getAddressCoordinatesBySelectAsArray(select);

            //Очистим карту
            mapDiv.empty();

            ymaps.ready(function(){

                //Строим карту
                var myMap = new ymaps.Map (mapDiv.attr('id'), {
                    center: [50, 50],
                    zoom: 10,
                    controls: ['zoomControl', 'typeSelector']
                });

                var myCollection = new ymaps.GeoObjectCollection();

                mapDiv.show();

                //Расставляем точки
                $(addresses).each(function(i){
                    var address = addresses[i];
                    var myPlacemark = new ymaps.Placemark([address.coordY, address.coordX], {
                        hintContent: lang.t("Выберите пункт: ") + address.addressInfo,
                        balloonContentHeader: address.addressInfo,
                        balloonContentBody: "\
                  <table border='0'>\
                     <tr>\
                        <td>" + lang.t('Город') + ":</td>\
                        <td>"+address.city+"</td>\
                     </tr>\
                     <tr>\
                        <td>" + lang.t('Код пункта') + ":</td>\
                        <td>"+address.code+"</td>\
                     </tr>\
                     <tr>\
                        <td>" + lang.t('Адрес') + ":</td>\
                        <td>"+address.addressInfo+"</td>\
                     </tr>\
                     <tr>\
                        <td>" + lang.t('Время работы') + ":</td>\
                        <td>"+address.worktime+"</td>\
                     </tr>\
                     <tr>\
                        <td>" + lang.t('Телефон') + ":</td>\
                        <td>"+address.phone+"</td>\
                     </tr>\
                  </table>"
                    });
                    /**
                     * Нажатие на открытие карты
                     */
                    myPlacemark.events.add('click', function () {
                        //Поставим код в скрытое поле
                        var option = $("option[value*='"+address.code+"']", select);
                        option.prop('selected', true);
                        select.trigger('change');
                    });
                    myCollection.add(myPlacemark);
                });

                myMap.geoObjects.add(myCollection);

                var pos = ymaps.util.bounds.getCenterAndZoom(myCollection.getBounds(), [mapDiv.width(), mapDiv.height()]);
                if (myCollection.getLength() == 1){
                    pos.zoom = 14;
                }
                myMap.setCenter(pos.center);
                myMap.setZoom(pos.zoom);

                //Убирает скролл на карте
                myMap.behaviors.disable('scrollZoom');
            });
        },

        /**
        * Получает все координаты адресов доставки в виде массива с доп данными.
        * Всё извлекается из выпадающего списка с выбором
        *
        * @param {object} select - объект выпадающего списка
        */
        getAddressCoordinatesBySelectAsArray = function(select)
        {
            var addresses = [];
            $("option", select).each(function(){
                var info = JSON.parse($(this).val());

                info.selected = $(this).prop('selected');
                addresses.push(info);
            });
            return addresses;
        },

        /**
        * Привязываем события
        */
        bindEvents = function(){
            //Смена доставки
            $('input[name="delivery"]', $this).on('change', changeDelivery);

            //Смена адреса доставки в выпадающем списке
            $(data.options.select, $this).on('change', changeDeliveryPickpoints);

            //Открытие карты
            $(data.options.openMapButton, $this).on('click', openMap);
        };

        if ( methods[method] ) {
            methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        }
   }     
})( jQuery );

$(document).ready(function(){
    $.deliveryWidjetCreator();
});
