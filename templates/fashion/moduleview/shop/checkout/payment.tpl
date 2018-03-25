<form method="POST" class="formStyle checkoutBox" id="order-form">
    {if $order->hasError()}
        <div class="pageError">
            {foreach $order->getErrors() as $item}
            <p>{$item}</p>
            {/foreach}
        </div>
    {/if}
    <input type="hidden" name="payment" value="0">
    <ul class="vertItems noPrice">
        {foreach $pay_list as $item}
        <li {if $item@first}class="first"{/if}>
            <div class="radio">
                <input type="radio" name="payment" value="{$item.id}" id="pay_{$item.id}" {if $order.payment==$item.id}checked{/if}>
                <span class="back"></span>
            </div>
            <div class="info">
                <div class="line">
                    <label for="pay_{$item.id}" class="title">{$item.title}</label>
                </div>
                <p class="descr">
                    {if !empty($item.picture)}
                       <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}"/>
                    {/if}
                {$item.description}</p>
            </div>
        </li>
        {/foreach}
    </ul>
    <div class="buttons">
        <input type="submit" value="Далее">
    </div>
</form>