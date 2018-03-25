{$shop_config = ConfigLoader::byModule('shop')}
{$catalog_config = ConfigLoader::byModule('catalog')}
{$product_items = $cart->getProductItems()}

<div class="cartPage" id="cartItems">
    <p class="h1">
        <span class="caption">{t}Корзина{/t}</span>
        {if !empty($cart_data.items)}
        <a href="{$router->getUrl('shop-front-cartpage', ["Act" => "cleanCart"])}" class="clearCart">{t}Очистить корзину{/t}</a>
        {/if}
    </p>
    {if !empty($cart_data.items)}
    <form method="POST" action="{$router->getUrl('shop-front-cartpage', ["Act" => "update"])}" id="cartForm">
    <input type="submit" class="hidden">
        <div class="cartTableBefore">
            <p class="price">{t}Цена{/t}</p>
            <p class="amount">{t}Количество{/t}</p>
        </div>
        {hook name="shop-cartpage:products" title="{t}Корзина:товары{/t}" product_items=$product_items}
            <div class="scrollCartWrap">
            <table class="cartTable">
                <tbody>
                {foreach $cart_data.items as $index => $item}
                    {$product = $product_items[$index].product}
                    {$cartitem = $product_items[$index].cartitem}
                    {if !empty($cartitem.multioffers)}
                           {$multioffers=unserialize($cartitem.multioffers)} 
                    {/if}
                    <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem{if $smarty.foreach.items.first} first{/if}">
                        <td class="image">
                            {$main_image = $product->getOfferMainImage($cartitem.offer)}
                            <a href="{$product->getUrl()}"><img src="{$main_image->getUrl('130','130')}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
                        </td>
                        <td class="title">
                            <a href="{$product->getUrl()}" class="text">{$cartitem.title}</a>
                            {if $product->isMultiOffersUse()}
                                    <div class="multiOffers">
                                        {foreach $product.multioffers.levels as $level}
                                            {if !empty($level.values)}
                                                <div class="multiofferTitle">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</div>
                                                <select name="products[{$index}][multioffers][{$level.prop_id}]" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}">
                                                    {foreach $level.values as $value}
                                                        <option {if $multioffers[$level.prop_id].value == $value.val_str}selected="selected"{/if} value="{$value.val_str}">{$value.val_str}</option>   
                                                    {/foreach}
                                                </select>
                                            {/if}
                                        {/foreach}
                                        {if $product->isOffersUse()}
                                            {foreach from=$product.offers.items key=key item=offer name=offers}
                                                <input id="offer_{$key}" type="hidden" name="hidden_offers" class="hidden_offers" value="{$key}" data-info='{$offer->getPropertiesJson()}' data-num="{$offer.num}"/>
                                                {if $cartitem.offer==$key}
                                                    <input type="hidden" name="products[{$index}][offer]" value="{$key}"/>
                                                {/if}
                                            {/foreach}
                                        {/if}
                                    </div>
                                {elseif $product->isOffersUse()}
                                    <div class="offers">
                                        <select name="products[{$index}][offer]" class="offer">
                                            {foreach from=$product.offers.items key=key item=offer name=offers}
                                                <option value="{$key}" {if $cartitem.offer==$key}selected{/if}>{$offer.title}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                {/if}
                            <p class="desc">{$product.short_description}</p>
                        </td>
                        <td class="amount">
                            <input type="number" nim="{$product->getAmountStep()}" step="{$product->getAmountStep()}" class="inp fieldAmount" value="{$cartitem.amount}" name="products[{$index}][amount]">
                            <div class="incdec">
                                <a href="#" class="inc" data-amount-step="{$product->getAmountStep()}"></a>
                                <a href="#" class="dec" data-amount-step="{$product->getAmountStep()}"></a>
                            </div>
                            <span class="unit">
                                {if $catalog_config.use_offer_unit}
                                    {$product.offers.items[$cartitem.offer]->getUnit()->stitle}
                                {else}
                                    {$product->getUnit()->stitle}
                                {/if}
                            </span>
                            <div class="error">{$item.amount_error}</div>
                        </td>
                        <td class="price">
                            {$item.cost}
                            <div class="discount">
                                {if $item.discount>0}
                                    {t}скидка{/t} {$item.discount}
                                {/if}
                            </div>
                        </td>
                        <td class="remove">
                            <a title="{t}Удалить товар из корзины{/t}" class="iconX" href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $index])}"></a>
                        </td>
                    </tr>
                    {$concomitant=$product->getConcomitant()}
                    {foreach $item.sub_products as $id => $sub_product_data}
                        {$sub_product=$concomitant[$id]}
                        <tr class="concomitant">
                            <td class="image"></td>
                            <td class="title">
                                <label>
                                    <input 
                                        class="fieldConcomitant" 
                                        type="checkbox" 
                                        name="products[{$index}][concomitant][]" 
                                        value="{$sub_product->id}"
                                        {if $sub_product_data.checked}
                                            checked="checked"
                                        {/if}
                                        >
                                    {$sub_product->title}
                                </label>
                            </td>
                            <td class="amount">
                                {if $shop_config.allow_concomitant_count_edit}
                                    <input type="number" min="{$sub_product->getAmountStep()}" step="{$sub_product->getAmountStep()}" class="inp fieldAmount concomitant" data-id="{$sub_product->id}" value="{$sub_product_data.amount}" name="products[{$index}][concomitant_amount][{$sub_product->id}]">
                                    <div class="incdec">
                                        <a href="#" class="inc" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                        <a href="#" class="dec" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                    </div>
                                {else}
                                    <span class="amountWidth">{$sub_product_data.amount}</span>
                                {/if}
                                <div class="discount">
                                    {if $sub_product_data.discount>0}
                                        {t}скидка{/t} {$sub_product_data.discount}
                                    {/if}
                                </div>
                                <div class="error">{$sub_product_data.amount_error}</div>
                            </td>
                            <td class="price">
                                {$sub_product_data.cost}
                            </td>
                            <td class="remove"></td>
                        </tr>
                    {/foreach}
                {/foreach}                            
                </tbody>
            </table>
            </div>
            <table class="cartTable">
                <tbody>
                {foreach $cart->getCouponItems() as $id => $item}
                    <tr class="coupons">
                        <td class="image"></td>
                        <td class="title">{t}Купон на скидку{/t} {$item.coupon.code}</td>
                        <td class="amount"></td>
                        <td class="price"></td>
                        <td class="remove">
                            <a title="{t}Удалить скидочный купон из корзины{/t}" class="iconX" href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $id])}"></a>
                        </td>
                    </tr>
                {/foreach}
                
                {if $cart_data.total_discount>0}
                    <tr class="orderDiscount">
                        <td class="image"></td>
                        <td class="title">{t}Скидка на заказ{/t}</td>
                        <td class="amount"></td>
                        <td class="price">{$cart_data.total_discount}</td>
                        <td class="remove"></td>
                    </tr>
                {/if}            
                </tbody>
            </table>
        {/hook}
        
        {hook name="shop-cartpage:summary" title="{t}Корзина:итог{/t}"}
            <div class="cartTableAfter">
                <span class="mobileWrapper">
                    <span class="cap">{t}Купон на скидку(если есть){/t}&nbsp; </span>
                    <input type="text" class="couponCode{if $cart->getUserError('coupon')!==false} hasError{/if}" name="coupon" value="{$coupon_code}"> 
                    <a class="applyCoupon">{t}Применить{/t}</a>
                </span>
                <p class="price"><span class="text">{t}Итого:{/t}</span>{$cart_data.total}</p>
                <div class="loader"></div>
            </div>
        {/hook}
        
        {hook name="shop-cartpage:bottom" title="{t}Корзина:подвал{/t}"}
            <div class="cartErrors" {if !empty($cart_data.errors)}style="display: block;"{/if}>
                {foreach $cart_data.errors as $error}
                    {$error}<br>
                {/foreach}
            </div>
            
            <div class="actionLine">
                <a href="{$router->getUrl('shop-front-checkout')}" class="submit colorButton{if $cart_data.has_error} disabled{/if}">{t}Оформить заказ{/t}</a>
                <a href="#" class="continue">{t}Вернуться к покупкам{/t}</a>
                {if $THEME_SETTINGS.enable_one_click_cart}            
                <a href="JavaScript:;" class="toggleOneClickCart blueButton">{t}Заказать по телефону{/t}</a>
                {/if}
            </div>
        {/hook}
    </form>
    {* Покупка в один клик в корзине *}
    {if $THEME_SETTINGS.enable_one_click_cart}            
        {moduleinsert name="\Shop\Controller\Block\OneClickCart"}
    {/if}
    {else}
        <div class="noEntity">{t}В корзине нет товаров{/t}</div>
    {/if}    
</div>