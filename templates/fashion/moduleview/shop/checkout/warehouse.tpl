<form method="POST" class="formStyle checkoutBox" id="order-form">
    {if $order->hasError()}
        <div class="pageError">
            {foreach $order->getErrors() as $item}
            <p>{$item}</p>
            {/foreach}
        </div>
    {/if}
    <input type="hidden" name="warehouse" value="0">
    <ul class="vertItems noPrice">
        {$pvzList = $order->getDelivery()->getTypeObject()->getOption('pvz_list')}
        {foreach $warehouses_list as $item}
            {if empty($pvzList) || in_array(0, $pvzList) || in_array($item.id, $pvzList)}
                <li {if $item@first}class="first"{/if}>
                    <div class="radio">
                        <input type="radio" name="warehouse" value="{$item.id}" id="wh_{$item.id}" {if ($order.warehouse>0)&&($order.warehouse==$item.id)}checked{elseif ($order.warehouse==0) && $item.default_house}checked{/if} >
                        <span class="back"></span>
                    </div>
                    <div class="info">
                        <div class="line">
                            <label for="wh_{$item.id}" class="title">{$item.title}</label>
                        </div>
                        <p class="descr">
                            {if !empty($item.image)}
                               <img class="logoService" src="{$item.__image->getUrl(100, 100, 'xy')}" alt="{$item.title}"/>
                            {/if}
                            {if !empty($item.adress)}Адрес: <span class="hl">{$item.adress}</span><br/>{/if} 
                            {if !empty($item.phone)}Телефон: <span class="hl">{$item.phone}</span><br/>{/if}
                            {if !empty($item.work_time)}Время работы: <span class="hl">{$item.work_time}</span>{/if}
                        </p>
                    </div>
                </li>
            {/if}
        {/foreach}
    </ul>
    <div class="buttons">
        <input type="submit" value="Далее">
    </div>
</form>