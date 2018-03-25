<a href="{$router->getUrl('shop-front-checkout')}" class="checkout{if !$cart_info.has_error && $cart_info.items_count} active{/if}" id="checkout">{t}Оформить заказ{/t}</a>
<div class="cart{if $cart_info.items_count} active{/if}" id="cart">
    <a href="{$router->getUrl('shop-front-cartpage')}" class="openCart showCart"><span class="text">{t}Корзина{/t}</span><i class="icon"></i></a>
    <span class="floatCartAmount">{$cart_info.items_count}</span>
    <span class="floatCartPrice">{$cart_info.total}</span>
</div>