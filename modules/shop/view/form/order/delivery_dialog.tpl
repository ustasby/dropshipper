<div class="formbox">
    {* Типы доставки, которые не требуют установки флага отправки на удалённый сервис доставки *}
    {$user_delivery_type=[
        'myself',
        'fixedpay',
        'universal',
        'manual'
    ]}
    <form id="deliveryAddForm" method="POST" action="{urlmake}" data-city-autocomplete-url="{$router->getAdminUrl('searchCity')}" data-order-block="#deliveryBlockWrapper" enctype="multipart/form-data" class="crud-form" data-dialog-options='{ "width":800, "height":700 }'>
        {hook name="shop-form-order-delivery_dialog:form" title="{t}Редактирование заказа - диалог доставки:форма{/t}"}
            <table class="otable">
                <tbody class="new-address">
                    <tr>
                        <td class="otitle">{t}Cпособ доставки{/t}: </td>
                        <td>
                            {$selected_delivery_type="myself"}
                            <select id="change_delivery" name="delivery">
                                {foreach $dlist as $category=>$delivery_list}
                                    <optgroup label="{$category}">
                                    {foreach $delivery_list as $item}
                                        {$delivery_type=$item->getTypeObject()->getShortName()}
                                        {if $item.id==$delivery_id}
                                            {$selected_delivery_type=$delivery_type}
                                        {/if}
                                        <option value="{$item.id}" data-delivery-query-flag="{if in_array($delivery_type, $user_delivery_type)}0{else}1{/if}" {if $item.id==$delivery_id}selected{/if}>
                                            {$item.title}{if !empty($item.admin_suffix)} ({$item.admin_suffix}){/if}
                                        </option>
                                    {/foreach}
                                    </optgroup>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>{t}Изменяемый адрес{/t}:</td>
                        <td>
                        <select name="use_addr" id="change_addr" data-url="{adminUrl do=getAddressRecord}">
                            {foreach from=$address_list item=item}
                                <option value="{$item.id}" {if $current_address.id==$item.id}selected{/if}>{$item->getLineView()}</option>
                            {/foreach}
                            <option value="0">{t}Новый адрес для заказа{/t}</option>
                        </select>
                        <div class="fieldhelp">{t}Внимание! если этот адрес используется в других заказах, то он также будет изменен.{/t}</div>
                        </td>
                    </tr>
                </tbody>

                <tbody class="address_part">
                    {$address_part}
                </tbody>
                
                {if $order.id > 0}
                    <tbody>
                        <tr class="last">
                            <td class="caption">{t}Стоимость{/t}:</td>
                            <td>
                                <input size="10" type="text" maxlength="20" value="{$order.user_delivery_cost}" name="user_delivery_cost">
                                <div class="fieldhelp">{t}Если стоимость доставки не указана, то сумма доставки будет рассчитана автоматически.{/t}</div>
                            </td>
                        </tr>
                    </tbody>
                {/if}
            </table>
        {/hook}
    </form>
    <script type="text/javascript">
        /**
        * Получает адрес для получения подсказок для города
        */
        function getCityAutocompleteUrl()
        {
            var form   = $( "#deliveryCityInput" ).closest('form'); //Объект формы
            var url    = form.data('city-autocomplete-url'); //Адрес для запросов
            
            var country_id = $( "#deliveryCountryIdSelect" ).val();
            var region_id  = $( "#deliveryRegionIdSelect" ).val(); 
       
            url += "&country_id=" + country_id + "&region_id=" + region_id;
            return url;
        }
    
        $(function() {
            /**
            * Назначаем действия, если всё успешно вернулось 
            */
            $('#deliveryAddForm').on('crudSaveSuccess', function(event, response) {
                if (response.success && response.insertBlockHTML){ //Если всё удачно и вернулся HTML для вставки в блок
                    var insertBlock = $(this).data('order-block');            
                    $(insertBlock).html(response.insertBlockHTML).trigger('new-content');
                    if (typeof(response.delivery)!='undefined'){ //Если указан id доставки
                       $('input[name="delivery"]').val(response.delivery); 
                    }
                    
                    if (typeof(response.user_delivery_cost)!='undefined'){ //Если указан id доставки
                       $('input[name="user_delivery_cost"]').val(response.user_delivery_cost); 
                    }
                    if (typeof(response.use_addr)!='undefined'){ //Если выбран адрес доставки
                       $('input[name="use_addr"]').val(response.use_addr); 
                    }
                    if (typeof(response.address)!='undefined'){ //Если выбран адрес
                       for(var m in response.address){
                          $('input[name="address[' + m + ']"]').val(response.address[m]);  
                       } 
                    }             
                    //Снимем флаг показа дополнительных кнопок доставки 
                    if ($("#showDeliveryButtons").length){
                        $("#showDeliveryButtons").val(0);     
                    }                           
                    //Обновимм корзину, т.к. доставка может прибавить стоимость
                    $(this).closest('.dialog-window').on('dialogclose', function() {
                        $.orderEdit('refresh');
                    });
                }
            });
            
            /**
            * Смена выпадающего списка с адресами
            */
            $('#change_addr').on('change', function() {
                $.ajaxQuery({
                    url: $(this).data('url'),
                    data: {
                        'address_id': $(this).val()
                    },
                    success: function(response) {
                        $('.address_part').html(response.html);
                    }
                });
            });
            
            /**
            * Автозаполнение в строке с вводом города
            */
            $( "#deliveryCityInput" ).each(function() {
                var url = getCityAutocompleteUrl(); //Установка адреса
                
                $(this).autocomplete({
                    source: url,
                    minLength: 3,
                    select: function( event, ui ) {
                        var region_id  = ui.item.region_id;  //Выбранный регион
                        var country_id = ui.item.country_id; //Выбранная страна
                        var zipcode    = ui.item.zipcode;    //Индекс
                        
                        //Установка индекса
                        if (!$("#deliveryZipcodeInput").val()){
                            $("#deliveryZipcodeInput").val(zipcode);
                        }
                    },
                    messages: {
                        noResults: '',
                        results: function() {}
                    }
                }).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
                    ul.addClass('searchCityItems');
                    
                    return $( "<li />" )
                        .append( '<a>' + item.label + '</a>' )
                        .appendTo( ul );
                };
            });
            
            /**
            * Если меняется регион или страна в выпадающем списке
            */
            $("#deliveryRegionIdSelect, #deliveryCountryIdSelect").on('change', function(){
                var url = getCityAutocompleteUrl(); //Установка адреса
                $( "#deliveryCityInput" ).autocomplete('option', 'source', url);
            });
        });                                
    </script>
</div>