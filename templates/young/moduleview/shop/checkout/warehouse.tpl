<p class="checkoutMobileCaption">{t}Выбор склада{/t}</p>
{if $order->hasError()}
    <div class="pageError">
        {foreach $order->getErrors() as $item}
        <p>{$item}</p>
        {/foreach}
    </div>
{/if}
<form method="POST" class="formStyle checkoutForm" id="order-form">
    <input type="hidden" name="warehouse" value="0">
    <ul class="vertItems delivery">
        {$pvzList = $order->getDelivery()->getTypeObject()->getOption('pvz_list')}
        {foreach $warehouses_list as $item}
            {if empty($pvzList) || in_array(0, $pvzList) || in_array($item.id, $pvzList)}
                <li>
                    <div class="radioLine">
                        <span class="input">
                            <input type="radio" name="warehouse" value="{$item.id}" id="wh_{$item.id}" {if ($order.warehouse>0)&&($order.warehouse==$item.id)}checked{elseif ($order.warehouse==0) && $item.default_house}checked{/if} >
                            <label for="wh_{$item.id}">{$item.title}</label>
                        </span>
                    </div>
                    <div class="descr">
                        {if !empty($item.image)}
                               <img class="logoService" src="{$item.__image->getUrl(100, 100, 'xy')}" alt="{$item.title}"/>
                            {/if}
                            <p>
                            {if !empty($item.adress)}{t}Адрес:{/t} <span class="hl">{$item.adress}</span><br/>{/if}
                            {if !empty($item.phone)}{t}Телефон:{/t} <span class="hl">{$item.phone}</span><br/>{/if}
                            {if !empty($item.work_time)}{t}Время работы:{/t} <span class="hl">{$item.work_time}</span>{/if}
                            </p>
                    </div>
                </li>
            {/if}
        {/foreach}
    </ul>
    <div class="buttonLine">
        <input type="submit" value="{t}Далее{/t}">
    </div>
</form>