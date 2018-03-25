{if $elem.delivery>0}
    <input type="hidden" name="delivery" value="{$elem.delivery}"/>
    <input type="hidden" name="use_addr" value="{$elem.use_addr}"/>
    {assign var=hl value=["n","hl"]}  
    {* Блок о доставке *}
    <h3>Доставка <a href="{adminUrl do=deliveryDialog order_id=$elem.id delivery=$elem.delivery user_id=$user_id}" class="tool-edit crud-add editDeliveryButton" id="editDelivery" title="редактировать"></a></h3>

    <table class="order-table delivery-params">
        <tr class="{cycle values=$hl name="delivery"}">
            <td width="20">
                Тип
            </td>
            <td class="d_title">{$delivery.title}</td>
        </tr>
        <tr class="{cycle values=$hl name="delivery"}">
            <td>
                Индекс
            </td>
            <td class="d_zipcode">{$address.zipcode}</td>
        </tr>
        <tr class="{cycle values=$hl name="delivery"}">
            <td>Страна</td>
            <td class="d_country">{$address.country}</td>
        </tr>
        <tr class="{cycle values=$hl name="delivery"}">
            <td>Край/область</td>
            <td class="d_region">{$address.region}</td>
        </tr>
        <tr class="{cycle values=$hl name="delivery"}">
            <td>Город</td>
            <td class="d_city">{$address.city}</td>
        </tr>
        <tr class="{cycle values=$hl name="delivery"}">
            <td>Адрес</td>
            <td class="d_address">{$address.address}</td>
        </tr>
        {if !empty($warehouse_list)}
            <tr class="{cycle values=$hl name="delivery"}">
                <td>Склад</td>
                <td class="d_warehouse">
                    <select name="warehouse">
                        <option value="0">не выбран</option>
                        {foreach $warehouse_list as $warehouse}
                            <option value="{$warehouse.id}" {if $elem.warehouse == $warehouse.id}selected="selected"{/if}>{$warehouse.title}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        {/if}
        {if $courier_list}
            <tr class="{cycle values=$hl name="delivery"}">
                <td>Курьер</td>
                <td>
                    <select name="courier_id">
                        <option value="0">не выбран</option>
                        {foreach $courier_list as $courier_id => $courier}
                            <option value="{$courier_id}" {if $elem.courier_id == $courier_id}selected="selected"{/if}>{$courier}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        {/if}
        <tr class="{cycle values=$hl name="delivery"}">
            <td>Контактное лицо</td>
            <td class="d_contact_person"><input type="text" name="contact_person" value="{$elem.contact_person}" class="maxWidth"></td>
        </tr>
        <tr class="{cycle values=$hl name="delivery"}">
            <td>Трек-номер</td>
            <td class="d_contact_person"><input type="text" name="track-number" value="{$elem.track_number}" class="maxWidth"></td>
        </tr>
    </table> 
                   
    {if $elem.delivery_new_query}
        {* Выводим HTML с выбором опций в доставке, если такая имеется *}
        {$delivery->getTypeObject()->getAdminAddittionalHtml($elem)}    
    {/if}     
    {if $elem.id>0 && $show_delivery_buttons}
        {* Выводим дополнительный HTML типа доставки *}
        {$delivery->getTypeObject()->getAdminHTML($elem)}
        {* Конец Выводим дополнительный HTML типа доставки *}    
    {/if}
{else}
    <p class="emptyOrderBlock">Тип доставки не указан. <a href="{adminUrl do=deliveryDialog order_id=$elem.id user_id=$user_id}" class="crud-add editDeliveryButton">Указать доставку</a>.</p>
{/if}