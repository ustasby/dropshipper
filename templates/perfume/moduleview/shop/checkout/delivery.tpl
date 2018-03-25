<p class="checkoutMobileCaption">{t}Доставка{/t}</p>
{if $order->hasError()}
    <div class="pageError">
        {foreach $order->getErrors() as $item}
        <p>{$item}</p>
        {/foreach}
    </div>
{/if}
<form method="POST" class="formStyle checkoutForm" id="order-form">
    <input type="hidden" name="delivery" value="0">
    <ul class="vertItems">
        {foreach $delivery_list as $item}
            {$addittional_html = $item->getAddittionalHtml($order)}
            {$something_wrong = $item->getTypeObject()->somethingWrong($order)}
            <li>
                <div class="radioLine">
                    <span class="input">
                        <input type="radio" name="delivery" value="{$item.id}" id="dlv_{$item.id}" {if $order.delivery==$item.id}checked{/if} {if $something_wrong}disabled="disabled"{/if}>
                        <label for="dlv_{$item.id}">{$item.title}</label>
                    </span>
                    <span id="scost_{$item.id}" class="price">
                        {if $something_wrong}
                            <span style="color:red;">{$something_wrong}</span>
                        {else}
                            <span class="help">{$order->getDeliveryExtraText($item)}</span>
                            {$order->getDeliveryCostText($item)}
                        {/if}
                    </span>
                </div>
                <div class="descr">
                    {if !empty($item.picture)}
                       <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}" alt="{$item.title}"/>
                    {/if}
                    <p>{$item.description}</p>
                    <div class="additionalInfo">{$addittional_html}</div>
                </div>
            </li>
        {/foreach}
    </ul>
    
    <div class="buttonLine">
        <input type="submit" value="{t}Далее{/t}">
    </div>
</form>