<div class="payment-result">
    <div class="payment-result_content fail">
        <h2>
            <img src="{$THEME_IMG}/icons/big-fail.svg">
            <span>{t}Оплата не произведена{/t}</span>
        </h2>
        <p class="descr">
            {if $transaction->getOrder()->id}
                <a href="{$router->getUrl('shop-front-myorderview', ['order_id' => $transaction->getOrder()->order_num])}" class="colorButton">{t}перейти к заказу{/t}</a>
            {else}
                <a href="{$router->getUrl('shop-front-mybalance')}" class="colorButton">{t}перейти к лицевому счету{/t}</a>
            {/if}
        </p>
    </div>
</div>