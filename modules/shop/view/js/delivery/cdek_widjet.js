(function( $ ){
   $.CDEKWidjetCreator = function( method ) {
        //Найдём ближайший input c выбором
        var closestRadio = $('input[name="delivery"]:eq(0)');        
        var form         = closestRadio.closest('form'); //найдем форму
       
        var defaults = {            
           cdekDiv        : '.cdekWidjet',         //Контейнер куда будет вставлятся информация СДЭК
           deliveryExtra  : '.cdekDeliveryExtra',  //Скрытое поле с экстра информацией
           select         : '.cdekSelect',         //Выпадающий список с забора СДЭК
           additionalInfo : '.cdekAdditionalInfo', //Дополнительная информация СДЭК
           openMapButton  : '.cdekOpenMap',        //Кнопка открытия карты
           map            : '.cdekMap',            //Карта с выбором пукта забора
           yandexReady    : false                  //Флаг того, что яндекс загрузился
        },
        
        $this = form,
        data  = $this.data('cdekInfo');
        
        if (!$this.length) return;
        if (!data) { //Инициализация
            data = { options: defaults };
            $this.data('cdekInfo', data);
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
                
                //Смена адреса доставки в выпадающем списке
                $(data.options.select, $this).on('change', changeCDEKAdress);
                
                //Пройдёмся по контейнерам СДЭК
                presetCDEKDiv();
                
                //Открытие карты
                $(data.options.openMapButton, $this).on('click', openMap);

                //Навесим события на переключатели
                presetCDEKRadioEvents();
                
                //Разблокируем доставку СДЭК если она уже выбрана
                $(document).ready(function(){
                    checkSelectedCdekDelivery();
                });
                
                
                //Если контент обновился (для заказа на одной странице)
                $('body').on('new-content',function(){
                    
                    //Смена адреса доставки в выпадающем списке
                    $(data.options.select, $this).on('change', changeCDEKAdress);
                    //Пройдёмся по контейнерам СДЭК
                    presetCDEKDiv();
                    //Открытие карты
                    $(data.options.openMapButton, $this).on('click', openMap);
                    //Навесим события на переключатели
                    presetCDEKRadioEvents();
                    //Отработаем установку выборки СДЭК
                    var selectedCheckbox = $('input[name="delivery"]:checked');
                    if (typeof(selectedCheckbox.data('cdek-div-id'))!='undefined'){
                       $('select', selectedCheckbox.data('cdek-div-id')).change(); 
                       methods.showCDEK(selectedCheckbox);
                    }
                });
            },
            
            /**
            * Открывает доступность СДЭК для выбора
            * 
            * @param checkBoxObj  - объект с функциями СДЭК
            */
            showCDEK : function (checkBoxObj)
            {       
                
                var parent   = checkBoxObj.data('cdek-div-id');
                var selected = $(data.options.select+" option:selected",$(parent));
                var info     = selected.data('info');
                if (info){ //Если есть дополнительная информация
                    //Покажем доп. данные
                    insertAdditionalData($(data.options.additionalInfo,$(parent)), info);
                    //Запишем данные
                    $(data.options.deliveryExtra,$(parent)).prop('disabled',false).val(selected.val()); 
                }else{ //Если доставка до двери
                    $(data.options.deliveryExtra,$(parent)).prop('disabled',false);
                }
            },
            
            /**
            * Убирает доступность СДЭК для выбора из всех вариантов
            * и скрывает поля
            * 
            */
            clearCDEK : function ()
            {       
               $(data.options.cdekDiv+" "+data.options.deliveryExtra).prop('disabled',true); 
               $(data.options.cdekDiv+" "+data.options.map).empty().hide(); 
               $(data.options.cdekDiv+" "+data.options.additionalInfo).empty(); 
            }
        }
        
        //private
        
        /**
        * Срабатывает при нажатии на выбранную доставку.
        * Если у этой доставки есть признак что если есть признак, что это СДЭК,
        * то ничего не делаем. Если нет признака, что подгружалось, 
        * 
        */
        var setOrClearCDEKInfo = function (changeEvent, item) 
        {
            if (typeof(item)===null){
                var dataCDEKDiv = item.data('cdek-div-id');
            }else{
                var dataCDEKDiv = $(this).data('cdek-div-id');
            }

            if ( typeof(dataCDEKDiv) != 'undefined' ) { //Если это радиокнопка СДЭК
               methods.showCDEK($(this));  //Стартуем            
            }else{
               methods.clearCDEK();  //Стартуем    
            }
        },
        
        /**
        * Проходится по контейнерам СДЭК помечая радиокнопки маркером СДЭК
        * 
        */
        presetCDEKDiv = function (){
           $(data.options.cdekDiv, $this).each(function(i){
                 //Найдём и отметим подходящие радиокнопки, которые относятся к СДЭК
                 var deliveryId = $(this).data('delivery-id');
                 var radio      = $('input[name="delivery"][value="'+deliveryId+'"]');
                 //Перенесём данные к выборанной радио кнопке, чтобы можно было манипулировать
                 radio.data('cdek-div-id',"#"+$(this).attr('id'));
                 if (radio.prop('checked')){
                     $(data.options.select+" option:selected",$(this)).change();
                 }
           });
        },
        
        /**
        * Навешивает события на переключатели
        * 
        */
        presetCDEKRadioEvents = function (){
           //Получим все радио кнопки
           var radioboxes = $('input[type="radio"][name="delivery"]',$this); //найдем radio
            
           //Навесим переключение радиокнопок 
           radioboxes.each(function(){
                $(this).on('click', setOrClearCDEKInfo);
           }); 
        },
        
        /**
        * Вставляет доп. информацию о выбраном адресе забора товара
        * 
        * @param divObj - контейнер куда вставить
        * @param info - объект с информацией
        */
        insertAdditionalData = function (divObj, info)
        {
           divObj.html("\
            <span class='row'><span class='key'>" + lang.t('Город') + ":</span><span class='val'>"+info.city+"</span></span>\
            <span class='row'><span class='key'>" + lang.t('Адрес') + ":</span><span class='val'>"+info.adress+"</span></span>\
            <span class='row'><span class='key'>" + lang.t('Время работы') + ":</span><span class='val'>"+info.WorkTime+"</span></span>\
            <span class='row'><span class='key'>" + lang.t('Тел.') + ":</span><span class='val'>"+info.phone+"</span></span>\
            "); 
            //Если есть ограничение оплаты налиными
            if ( typeof(info.cashOnDelivery) != "undefined" ) {
                divObj.append("\
            <span class='row'><span class='key'>" + lang.t('Ограничение оплаты наличными при получении') + ":</span><span class='val'>"+info.city+"</span></span>");
            }
            if ( info.note.length>0 ){
               divObj.append("<span class='key'>" + lang.t('Заметка') + ":</span><span class='val'>"+info.note+"</span>"); 
            }
        },
        
        /**
        * Получает все координаты адресов доставки в виде массива с доп данными.
        * Всё извлекается из выпадающего списка с выбором
        * 
        * @param object select - объект выпадающего списка
        */
        getAddressCoorBySelectAsArray = function(select)
        {
           var addresses = [];
           $("option",select).each(function(i){
               var info = $(this).data('info');
               
               if ( $(this).prop('selected') ) {
                  info.selected = true; 
               }else{
                  info.selected = false;  
               }
               addresses.push(info);
           }); 
           return addresses;
        },
        
        
        /**
        * Открытие карты с пунктами выдачи товара
        */ 
        openMap = function ()
        {
           var parent = $(this).closest(data.options.cdekDiv); //Оборачивающий контейнер
           var mapDiv = $(data.options.map,parent);            //Карта
           var select = $(data.options.select,parent);         //Выпадающий список
           var hidden = $(data.options.deliveryExtra,parent);  //Скрытое поле
           
           //Соберём доступные координаты
           var addresses = getAddressCoorBySelectAsArray(select);
           
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
                   var adress = addresses[i];
                   var myPlacemark = new ymaps.Placemark([adress.coordY, adress.coordX], { 
                      hintContent: lang.t("Выберите пункт: ")+adress.adress,
                      balloonContentHeader: adress.adress, 
                      balloonContentBody: "\
                      <table border='0'>\
                         <tr>\
                            <td>" + lang.t('Город') + ":</td>\
                            <td>"+adress.city+"</td>\
                         </tr>\
                         <tr>\
                            <td>" + lang.t('Код пункта') + ":</td>\
                            <td>"+adress.code+"</td>\
                         </tr>\
                         <tr>\
                            <td>" + lang.t('Адрес') + ":</td>\
                            <td>"+adress.adress+"</td>\
                         </tr>\
                         <tr>\
                            <td>" + lang.t('Время работы') + ":</td>\
                            <td>"+adress.WorkTime+"</td>\
                         </tr>\
                         <tr>\
                            <td>" + lang.t('Телефон') + ":</td>\
                            <td>"+adress.phone+"</td>\
                         </tr>\
                         <tr>\
                            <td>" + lang.t('Заметки') + ":</td>\
                            <td>"+adress.note+"</td>\
                         </tr>\
                      </table>", 
                   });
                   myPlacemark.events.add('click', function () {
                       //Поставим код в скрытое поле
                       var option = $("option[value*='"+adress.code+"']",select);
                       option.prop('selected',true);
                       hidden.val(option.val());
                       
                       var info     = option.data('info');
                       if (info){
                           //Покажем доп. данные
                           insertAdditionalData($(data.options.additionalInfo, $(parent)), info);
                       }
                   });
                   myCollection.add(myPlacemark);
               });
               
               myMap.geoObjects.add(myCollection);               

               var pos = ymaps.util.bounds.getCenterAndZoom(myCollection.getBounds(), [mapDiv.width(), mapDiv.height()]);
               if (myCollection.getLength() == 1) pos.zoom = 14;
               myMap.setCenter(pos.center);
               myMap.setZoom(pos.zoom);
               
               //Убирает скролл на карте
               myMap.behaviors.disable('scrollZoom');
           });
           
           
        },
        
        /**
        * Проверяет выбрана ли доставка СДЭК и отрабатывает выбор адреса доставки
        */
        checkSelectedCdekDelivery = function(){
            var selectedCheckbox = $('input[name="delivery"]:checked');
            if (typeof(selectedCheckbox.data('cdek-div-id'))!='undefined'){
               $('select', selectedCheckbox.data('cdek-div-id')).change(); 
               methods.showCDEK(selectedCheckbox);
            } 
        },
        
        /**
        * Смена адреса доставки в выпадающем списке
        * 
        */
        changeCDEKAdress = function(event, item)
        {
            if (event === null){ //Если сработало событие
               var parent = item.closest(data.options.cdekDiv); 
               if (parent.length==0){ //Если это не СДЭК
                   return false;
               }
            }else{ //Если установли вручную
               var parent = $(this).closest(data.options.cdekDiv); 
            }    
            
            var selected = $(data.options.select+" option:selected",$(parent));
            var info     = selected.data('info');
            if (info){
                //Покажем доп. данные
                insertAdditionalData($(data.options.additionalInfo, $(parent)),info);
                //Запишем данные
                $(data.options.deliveryExtra, $(parent)).val(selected.val());
            }
        }
        
       
        if ( methods[method] ) {
            methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        }
   }     
})( jQuery );

$(document).ready(function(){
    $.CDEKWidjetCreator();
});