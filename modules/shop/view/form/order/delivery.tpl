<h3>{t}Доставка{/t} {if $elem.delivery>0}<a href="{adminUrl do=deliveryDialog order_id=$elem.id delivery=$elem.delivery user_id=$user_id}" class="crud-add editDeliveryButton m-l-10" id="editDelivery" title="{t}редактировать{/t}"><i class="zmdi zmdi-edit"></i></a>{/if}</h3>

{if $elem.delivery>0}
    <input type="hidden" name="delivery" value="{$elem.delivery}"/>
    <input type="hidden" name="use_addr" value="{$elem.use_addr}"/>
    {* Блок о доставке *}

    <table class="otable delivery-params">
        <tr>
            <td class="otitle">
                {t}Тип{/t}
            </td>
            <td class="d_title">{$delivery.title}</td>
        </tr>
        <tr>
            <td class="otitle">
                {t}Индекс{/t}
            </td>
            <td class="d_zipcode">{$address.zipcode|default:"{t}- не указано -{/t}"}</td>
        </tr>
        <tr>
            <td class="otitle">{t}Страна{/t}</td>
            <td class="d_country">{$address.country|default:"{t}- не указано -{/t}"}</td>
        </tr>
        <tr>
            <td class="otitle">{t}Край/область{/t}</td>
            <td class="d_region">{$address.region|default:"{t}- не указано -{/t}"}</td>
        </tr>
        <tr>
            <td class="otitle">{t}Город{/t}</td>
            <td class="d_city">{$address.city|default:"{t}- не указано -{/t}"}</td>
        </tr>
        <tr>
            <td class="otitle">{t}Адрес{/t}</td>
            <td class="d_address">{$address->getLineView(false)|default:"{t}- не указано -{/t}"}</td>
        </tr>
        {if $address.subway}
        <tr>
            <td class="otitle">{t}Станция метро{/t}</td>
            <td class="d_address">{$address.subway}</td>
        </tr>
        {/if}
        {if !empty($warehouse_list)}
            <tr>
                <td class="otitle">{t}Склад{/t}</td>
                <td class="d_warehouse">
                    <select name="warehouse">
                        <option value="0">{t}не выбран{/t}</option>
                        {foreach $warehouse_list as $warehouse}
                            <option value="{$warehouse.id}" {if $elem.warehouse == $warehouse.id}selected="selected"{/if}>{$warehouse.title}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        {/if}
        {if $courier_list}
            <tr>
                <td class="otitle">{t}Курьер{/t}</td>
                <td>
                    <select name="courier_id">
                        <option value="0">{t}не выбран{/t}</option>
                        {foreach $courier_list as $courier_id => $courier}
                            <option value="{$courier_id}" {if $elem.courier_id == $courier_id}selected="selected"{/if}>{$courier}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        {/if}
        <tr>
            <td class="otitle">{t}Контактное лицо{/t}</td>
            <td class="d_contact_person"><input type="text" name="contact_person" value="{$elem.contact_person}" class="maxWidth"></td>
        </tr>

        {$order_delivery_fields}
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
    <p class="emptyOrderBlock">{t}Тип доставки не указан.{/t} <a href="{adminUrl do=deliveryDialog order_id=$elem.id user_id=$user_id}" class="crud-add editDeliveryButton u-link">{t}Указать доставку{/t}</a>.</p>
{/if}