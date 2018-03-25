<div class="paymentResult">
    <div class="fail"><span class="text">{t}Оплата не произведена{/t}</span></div><br>
    {if $transaction->getOrder()->id}
        <a href="{$router->getUrl('shop-front-myorderview', ['order_id' => $transaction->getOrder()->order_num])}" class="colorButton">{t}перейти к заказу{/t}</a>
    {else}
        <a href="{$router->getUrl('shop-front-mybalance')}" class="colorButton">{t}перейти к лицевому счету{/t}</a>
    {/if}
</div>