{$shop_config = ConfigLoader::byModule('shop')}
{$catalog_config = ConfigLoader::byModule('catalog')}
{$product_items = $cart->getProductItems()}
{$floatCart = (int)$smarty.request.floatCart}
{if $floatCart}
    <div class="viewport" id="cartItems">
        <div class="cartFloatBlock">
            <i class="corner"></i>
            {if !empty($cart_data.items)}
            <form method="POST" action="{$router->getUrl('shop-front-cartpage', ["Act" => "update", "floatCart" => $floatCart])}" id="cartForm">
                <div class="cartHeader">
                    <a href="{$router->getUrl('shop-front-cartpage', ["Act" => "cleanCart", "floatCart" => $floatCart])}" class="clearCart">очистить корзину</a>
                    <a class="iconX closeDlg"></a>
                    <img src="{$THEME_IMG}/loading.gif" class="loader">
                </div>
                {hook name="shop-cartpage:products" title="{t}Корзина:товары{/t}" product_items=$product_items}
                    <div class="scrollBox">
                        <table class="items cartTable">
                            <tbody>
                                {foreach $cart_data.items as $index => $item}
                                    {$product = $product_items[$index].product}
                                    {$cartitem = $product_items[$index].cartitem}
                                    {if !empty($cartitem.multioffers)}
                                        {$multioffers=unserialize($cartitem.multioffers)}
                                    {/if}
                                <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem{if $item@first} first{/if}">
                                    <td class="image"><a href="{$product->getUrl()}"><img src="{$product->getOfferMainImage($cartitem.offer, 81, 81, 'axy')}" alt="{$product.title}"/></a></td>
                                    <td class="title"><a href="{$product->getUrl()}">{$cartitem.title}</a>
                                            {$offer_barcode=$product->getBarcode($cartitem.offer)}
                                            {if $offer_barcode}{* Артикул комлпектации *}
                                                <p class="barcode">Артикул: {$product->getBarcode($cartitem.offer)}</p>
                                            {elseif $product.barcode} {* Артикул товара *}
                                                <p class="barcode">Артикул: {$product.barcode}</p>
                                            {/if}
                                            
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
                                    </td>
                                    <td class="price">
                                        <div class="amount">
                                            <input type="hidden" step="{$product->getAmountStep()}" class="fieldAmount" value="{$cartitem.amount}" name="products[{$index}][amount]"/> 
                                            <a class="dec" data-amount-step="{$product->getAmountStep()}"></a>
                                            <span class="num" title="Количество">{$cartitem.amount}</span> 
                                            
                                            <span class="unit">
                                            {if $catalog_config.use_offer_unit}
                                                {$product.offers.items[$cartitem.offer]->getUnit()->stitle}
                                            {else}
                                                {$product->getUnit()->stitle|default:"шт."}
                                            {/if}
                                            </span>
                                            
                                            <a class="inc" data-amount-step="{$product->getAmountStep()}"></a>
                                        </div>
                                        <div class="cost">{$item.cost}</div>
                                        <div class="discount">
                                            {if $item.discount>0}
                                                скидка {$item.discount}
                                            {/if}
                                        </div>
                                        <div class="error">{$item.amount_error}</div>
                                    </td>
                                    <td class="remove"><a href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $index, "floatCart" => $floatCart])}" title="Удалить товар из корзины" class="iconRemove"></a></td>
                                </tr>
                                    {assign var=concomitant value=$product->getConcomitant()}
                                    {foreach from=$item.sub_products key=id item=sub_product_data}
                                        {assign var=sub_product value=$concomitant[$id]}
                                        <tr>
                                            <td colspan="2" class="title">
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
                                            <td class="price">
                                                {if $shop_config.allow_concomitant_count_edit}
                                                    <div class="amount">
                                                        <input type="hidden" step="{$sub_product->getAmountStep()}" class="fieldAmount concomitant" data-id="{$sub_product->id}" value="{$sub_product_data.amount}" name="products[{$index}][concomitant_amount][{$sub_product->id}]"> 
                                                        <a class="dec" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                                        <span class="num" title="Количество">{$sub_product_data.amount}</span> {$product->getUnit()->stitle|default:"шт."}
                                                        <a class="inc" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                                    </div>
                                                {else}
                                                    <div class="amount" title="Количество">{$sub_product_data.amount} {$sub_product->getUnit()->stitle|default:"шт."}</div>
                                                {/if}
                                                <div class="cost">{$sub_product_data.cost}</div>
                                                <div class="discount">
                                                    {if $sub_product_data.discount>0}
                                                    скидка {$sub_product_data.discount}
                                                    {/if}
                                                </div>
                                                <div class="error">{$sub_product_data.amount_error}</div>
                                            </td>
                                            <td class="remove"></td>
                                        </tr>
                                    {/foreach}
                                {/foreach}
                                {foreach from=$cart->getCouponItems() key=id item=item}
                                <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem couponLine">
                                    <td colspan="2" class="title">Купон на скидку {$item.coupon.code}</td>
                                    <td class="price"></td>
                                    <td class="remove"><a href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $id, "floatCart" => $floatCart])}" title="Удалить скидочный купон" class="iconRemove"></a></td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/hook}
                {hook name="shop-cartpage:summary" title="{t}Корзина:итог{/t}"}
                    <div class="cartFooter{if $coupon_code} onPromo{/if}">
                        <a class="hasPromo" onclick="$(this).parent().toggleClass('onPromo')">У меня есть промо-код</a>
                        <div class="promo">
                            Промо-код: &nbsp;<input type="text" name="coupon" value="{$coupon_code}" class="couponCode">&nbsp; 
                            <a class="applyCoupon">применить</a>
                        </div>
                    </div>
                {/hook}                    
                {hook name="shop-cartpage:bottom" title="{t}Корзина:подвал{/t}"}
                    <div class="cartError {if empty($cart_data.errors)}hidden{/if}">
                        {foreach from=$cart_data.errors item=error}
                            {$error}<br>
                        {/foreach}
                    </div>
                {/hook}
            </form>
            {* Покупка в один клик в корзине *}
            {if $THEME_SETTINGS.enable_one_click_cart}            
                <a href="JavaScript:;" class="toggleOneClickCart">Заказать по телефону</a>
                {moduleinsert name="\Shop\Controller\Block\OneClickCart"}
            {/if}
            {else}
            <div class="emptyCart">
                <a class="iconX closeDlg"></a>
                В корзине нет товаров
            </div>            
            {/if}
        </div>
    </div>
{else}
    <div class="cartPage" id="cartItems">
        <h1>
            <span class="caption">Корзина</span>
            {if !empty($cart_data.items)}
            <a href="{$router->getUrl('shop-front-cartpage', ["Act" => "cleanCart"])}" class="clearCart">Очистить корзину</a>
            {/if}
        </h1>
        {if !empty($cart_data.items)}
        <form method="POST" action="{$router->getUrl('shop-front-cartpage', ["Act" => "update"])}" id="cartForm" class="formStyle">
            <input type="submit" class="hidden">
            {hook name="shop-cartpage:products" title="{t}Корзина:товары{/t}"}
                <div class="scrollCartWrap">
                    <table class="cartTablePage cartTable">
                        <thead>
                            <tr>
                                <td colspan="2">Товар</td>
                                <td>Количество</td>
                                <td>Цена</td>
                                <td></td>
                            </tr>
                        </thead>            
                        <tbody>
                        {foreach $cart_data.items as $index => $item}
                            {$product=$product_items[$index].product}
                            {$cartitem=$product_items[$index].cartitem}
                            {if !empty($cartitem.multioffers)}
                                   {$multioffers=unserialize($cartitem.multioffers)} 
                            {/if}                    
                            <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem{if $smarty.foreach.items.first} first{/if}">
                                <td class="image">
                                    <a href="{$product->getUrl()}"><img src="{$product->getOfferMainImage($cartitem.offer, 78, 109)}" alt="{$product.title}"/></a>
                                </td>
                                <td class="title">
                                    <a href="{$product->getUrl()}" class="text">{$product.title}</a>
                                    
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
                                    
                                    {$offer_barcode=$product->getBarcode($cartitem.offer)}
                                    {if $offer_barcode}{* Артикул комлпектации *}
                                        <p class="barcode">Артикул: {$product->getBarcode($cartitem.offer)}</p>
                                    {elseif $product.barcode} {* Артикул товара *}
                                        <p class="barcode">Артикул: {$product.barcode}</p>
                                    {/if}
                                    <p class="desc">{$product.short_description}</p>
                                </td>
                                <td class="amount">
                                    <input type="number" step="{$product->getAmountStep()}" class="inp fieldAmount" value="{$cartitem.amount}" name="products[{$index}][amount]">
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
                                        скидка {$item.discount}
                                        {/if}
                                    </div>
                                </td>
                                <td class="remove">
                                    <a title="Удалить товар из корзины" class="removeItem" href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $index])}"></a>
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
                                            <input type="number" step="{$sub_product->getAmountStep()}" class="inp fieldAmount concomitant" data-id="{$sub_product->id}" value="{$sub_product_data.amount}" name="products[{$index}][concomitant_amount][{$sub_product->id}]">
                                            <div class="incdec">
                                                <a href="#" class="inc" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                                <a href="#" class="dec" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                            </div>
                                        {else}
                                            <span class="amountWidth">{$sub_product_data.amount}</span>
                                        {/if}
                                        <div class="discount">
                                            {if $sub_product_data.discount>0}
                                            скидка {$sub_product_data.discount}
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
            
                <table class="cartTablePage cartTable">
                    <tbody>
                    {foreach $cart->getCouponItems() as $id => $item}
                        <tr class="coupons" data-id="{$id}">
                            <td class="image"></td>
                            <td class="title">Купон на скидку {$item.coupon.code}</td>
                            <td class="amount"></td>
                            <td class="price"></td>
                            <td class="remove">
                                <a title="Удалить скидочный купон из корзины" class="iconRemove" href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $id])}"></a>
                            </td>
                        </tr>
                    {/foreach}           
                    </tbody>
                </table>
            {/hook}
            
            {hook name="shop-cartpage:summary" title="{t}Корзина:итог{/t}"}
                <div class="cartTableAfter">
                    <p class="price">{$cart_data.total}</p>
                    <div class="loader"></div>            
                    
                    <span class="cap">Купон на скидку(если есть)&nbsp; </span>
                    <input type="text" class="couponCode{if $cart->getUserError('coupon')!==false} hasError{/if}" name="coupon" value="{$coupon_code}"> 
                    <a data-href="{$router->getUrl('shop-front-cartpage', ["Act" => "applyCoupon"])}" class="applyCoupon">Применить</a>
                </div>
            {/hook}
            
            {hook name="shop-cartpage:bottom" title="{t}Корзина:подвал{/t}"}
                {if !empty($cart_data.errors)}
                <div class="cartErrors">
                    {foreach $cart_data.errors as $error}
                        {$error}<br>
                    {/foreach}
                </div>
                {/if}
            
                <div class="actionLine">
                    <a href="JavaScript:;" class="button continue">Продолжить покупки</a>
                    <a href="{$router->getUrl('shop-front-checkout')}" class="submit button color{if $cart_data.has_error} disabled{/if}">Оформить заказ</a>
                    {if $THEME_SETTINGS.enable_one_click_cart}            
                    <a href="JavaScript:;" class="button toggleOneClickCart">Заказать по телефону</a>
                    {/if}
                </div>
            {/hook}
        </form>
        {* Покупка в один клик в корзине *}
        {if $THEME_SETTINGS.enable_one_click_cart}            
            {moduleinsert name="\Shop\Controller\Block\OneClickCart"}
        {/if}
        {else}
        <div class="emptyCart">
            В корзине нет товаров
        </div>                    
        {/if}
    </div>
{/if}