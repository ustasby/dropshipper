<form method="POST" class="formStyle checkoutBox" id="order-form">
    {if $order->hasError()}
        <div class="pageError">
            {foreach $order->getErrors() as $item}
            <p>{$item}</p>
            {/foreach}
        </div>
    {/if}
    <input type="hidden" name="delivery" value="0">
    <ul class="vertItems">
        {foreach $delivery_list as $item}
            {$addittional_html = $item->getAddittionalHtml($order)}
            {$something_wrong = $item->getTypeObject()->somethingWrong($order)} 
            <li {if $item@first}class="first"{/if}>
                <div class="radio">
                    <input type="radio" name="delivery" value="{$item.id}" id="dlv_{$item.id}" {if $order.delivery==$item.id}checked{/if} {if $something_wrong}disabled="disabled"{/if}>
                    <span class="back"></span>
                </div>
                <div class="info">
                    <div class="line">
                        <span id="scost_{$item.id}" class="price">
                            {if $something_wrong}
                                <span style="color:red;">{$something_wrong}</span>
                            {else}
                                <span class="help">{$order->getDeliveryExtraText($item)}</span>
                                {$order->getDeliveryCostText($item)}
                            {/if}
                        </span>
                        <label for="dlv_{$item.id}" class="title">{$item.title}</label>                    
                    </div>
                    <p class="descr">
                        {if !empty($item.picture)}
                           <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}" alt="{$item.title}"/>
                        {/if}
                    {$item.description}</p>
                    <div class="additionalInfo">{$addittional_html}</div>
                </div>
            </li>
        {/foreach}
    </ul>
    <div class="buttons">
        <input type="submit" value="Далее">
    </div>
</form>