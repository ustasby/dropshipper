<p class="checkoutMobileCaption">{t}Оплата{/t}</p>
{if $order->hasError()}
    <div class="pageError">
        {foreach $order->getErrors() as $item}
        <p>{$item}</p>
        {/foreach}
    </div>
{/if}
<form method="POST" class="formStyle checkoutForm" id="order-form">
    <input type="hidden" name="payment" value="0">
    <ul class="vertItems delivery">
        {foreach $pay_list as $item}
        <li>
            <div class="radioLine">
                <span class="input">
                    <input type="radio" name="payment" value="{$item.id}" id="pay_{$item.id}" {if $order.payment==$item.id}checked{/if}>
                    <label for="pay_{$item.id}">{$item.title}</label>
                </span>
            </div>
            <div class="descr">
                {if !empty($item.picture)}
                   <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}" alt="{$item.title}"/>
                {/if}                                    
                <p>{$item.description}</p>
            </div>
        </li>
        {/foreach}
    </ul>
    <div class="buttonLine">
        <input type="submit" value="{t}Далее{/t}">
    </div>
</form>