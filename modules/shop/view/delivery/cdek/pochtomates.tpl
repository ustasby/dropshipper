{addjs file="//api-maps.yandex.ru/2.1/?lang=ru_RU" basepath="root"}
{addjs file="%shop%/delivery/cdek_widjet.js"}

<div id="cdekWidjet{$delivery.id}" class="cdekWidjet" data-delivery-id="{$delivery.id}">
    {if empty($errors) && !empty($pochtomates)}
        <div class="title">
            {t}Выберите место получения товара{/t}:
        </div>
        <div class="additionalTitleInfo">
           {$date_min=$extra_info.deliveryDateMin} 
           {$date_max=$extra_info.deliveryDateMax} 
           ({t}Ориентировочная дата доставки{/t} 
           {if $date_min!=$date_max}
                с {$date_min|dateformat:"@date"} по {$date_max|dateformat:"@date"})
           {else}
                {$date_min|dateformat:"@date"})
           {/if}
        </div>
        
        <select id="cdekSelect{$delivery.id}" class="cdekSelect">
            {foreach $pochtomates as $pochtomat}
                <option value='{literal}{{/literal}"code":"{$pochtomat['Code']}","cityCode":"{$pochtomat['CityCode']}","addressInfo":"{$pochtomat['City']}, {$pochtomat['Address']}","tariffId":"{$cdek->getTariffId()}"{if isset($pochtomat['cashOnDelivery'])},"cashOnDelivery":"{$pochtomat['cashOnDelivery']}"{/if}}' data-info='{ "note":"{$pochtomat['Note']}", "city":"{$pochtomat['City']}", "code":"{$pochtomat['Code']}" , "cityCode" : "{$pochtomat['cityCode']}", "coordY":"{$pochtomat['coordY']}", "coordX":"{$pochtomat['coordX']}", "WorkTime":"{$pochtomat['WorkTime']}", "phone":"{$pochtomat['Phone']}", "adress":"{$pochtomat['Address']}"{if isset($pochtomat['cashOnDelivery'])},"cashOnDelivery":"{$pochtomat['cashOnDelivery']}{/if} }'>{$pochtomat['City']}, {$pochtomat['Address']}</option>
            {/foreach}
        </select>
        <a class="cdekOpenMap formSave" title="Скрыть карту">Открыть карту</a>
        <div class="cdekAdditionalInfo"></div>
        <div>
            Неголосовой бесплатный контакт-центр 8 (800) 250-14-05
        </div>
        <input id="cdekInputMap{$delivery.id}" class="cdekDeliveryExtra" type="hidden" name="delivery_extra[value]" value="" disabled="disabled"/>
        <div id="cdekMap{$delivery.id}" class="cdekMap" style="display:none;"></div>
    {else}
        {$address=$order->getAddress()}
        <input id="cdekInputMap{$delivery.id}" class="cdekDeliveryExtra" type="hidden" name="delivery_extra[value]" value='{literal}{{/literal}"tariffId":"{$extra_info.tariffId}", "zipcode":"{$address.zipcode}"}' disabled="disabled"/>
    {/if}
</div>
