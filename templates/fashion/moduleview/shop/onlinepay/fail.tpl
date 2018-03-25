<div class="paymentResult fail profile">
    <h2>Оплата не произведена</h2>
    <p class="descr">
    {if $transaction->getOrder()->id}
        <a href="{$router->getUrl('shop-front-myorderview', ['order_id' => $transaction->getOrder()->order_num])}" class="colorButton">перейти к заказу</a>
    {else}
        <a href="{$router->getUrl('shop-front-mybalance')}" class="colorButton">перейти к лицевому счету</a>
    {/if}</p>
</div>