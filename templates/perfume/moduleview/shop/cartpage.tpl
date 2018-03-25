{$shop_config=ConfigLoader::byModule('shop')}
{$catalog_config=ConfigLoader::byModule('catalog')}
{$product_items=$cart->getProductItems()}
{$floatCart=(int)$smarty.request.floatCart}
{if $floatCart}
    <div class="container_12" id="cartItems">
        <div class="cartFloatBlock">
            <i class="corner"></i>
            {if !empty($cart_data.items)}
            <form method="POST" action="{$router->getUrl('shop-front-cartpage', ["Act" => "update", "floatCart" => $floatCart])}" id="cartForm">
                <div class="cartHeader">
                    <a href="{$router->getUrl('shop-front-cartpage', ["Act" => "cleanCart", "floatCart" => $floatCart])}" class="clearCart">{t}очистить корзину{/t}</a>
                    <a class="iconX closeDlg"></a>
                    <img src="{$THEME_IMG}/loading_black.gif" class="loader" alt=""/>
                </div>
                {hook name="shop-cartpage:products" title="{t}Корзина:товары{/t}" product_items=$product_items}
                    <div class="scrollBox">
                    <table class="cartTable">
                        <tbody>
                            {foreach $cart_data.items as $index => $item}
                                {$product = $product_items[$index].product}
                                {$cartitem = $product_items[$index].cartitem}
                                {if !empty($cartitem.multioffers)}
                                       {$multioffers=unserialize($cartitem.multioffers)} 
                                {/if}
                            <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem{if $item@first} first{/if}">
                                {$main_image=$product->getOfferMainImage($cartitem.offer)}
                                <td class="image"><a href="{$product->getUrl()}"><img src="{$main_image->getUrl(81, 81, 'axy')}" alt="{$main_image.title|default:"{$product.title}"}"/></a></td>
                                <td class="title"><a href="{$product->getUrl()}">{$cartitem.title}</a>

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
                                        <input type="hidden" min="{$product->getAmountStep()}" step="{$product->getAmountStep()}" class="fieldAmount" value="{$cartitem.amount}" name="products[{$index}][amount]"> 
                                        <a class="dec" data-amount-step="{$product->getAmountStep()}"></a>
                                        <span class="num">{$cartitem.amount}</span> 
                                        
                                        <span class="unit">
                                            {if $catalog_config.use_offer_unit}
                                                {$product.offers.items[$cartitem.offer]->getUnit()->stitle}
                                            {else}
                                                {$product->getUnit()->stitle}
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
                                <td class="remove"><a href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $index, "floatCart" => $floatCart])}" title="{t}Удалить товар из корзины{/t}" class="iconX"></a></td>
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
                                                    <input type="hidden" min="{$sub_product->getAmountStep()}" step="{$sub_product->getAmountStep()}" class="fieldAmount concomitant" data-id="{$sub_product->id}" value="{$sub_product_data.amount}" name="products[{$index}][concomitant_amount][{$sub_product->id}]"> 
                                                    <a class="dec" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                                    <span class="num">{$sub_product_data.amount}</span> {$product->getUnit()->stitle}
                                                    <a class="inc" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                                </div>
                                            {else}
                                                <div class="amount">{$sub_product_data.amount} {$sub_product->getUnit()->stitle}</div>
                                            {/if}
                                            <div class="cost">{$sub_product_data.cost}</div>
                                            <div class="discount">
                                                {if $sub_product_data.discount>0}
                                                    {t}скидка{/t} {$sub_product_data.discount}
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
                                <td class="remove"><a href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $id, "floatCart" => $floatCart])}" title="{t}Удалить скидочный купон{/t}" class="iconX"></a></td>
                            </tr>
                            {/foreach}                        
                        </tbody>
                    </table>
                </div>
                {/hook}
                
                {hook name="shop-cartpage:summary" title="{t}Корзина:итог{/t}"}
                    <div class="cartFooter{if $coupon_code} onPromo{/if}">
                        <a class="hasPromo" onclick="$(this).parent().toggleClass('onPromo')">{t}У меня есть промо-код{/t}</a>
                        <div class="promo">
                            {t}Промо-код{/t}: &nbsp;<input type="text" name="coupon" value="{$coupon_code}" class="couponCode">&nbsp;
                            <a class="applyCoupon">{t}применить{/t}</a>
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
                <a href="JavaScript:;" class="toggleOneClickCart">{t}Заказать по телефону{/t}</a>
                {moduleinsert name="\Shop\Controller\Block\OneClickCart"}            
            {/if}
            {else}
            <div class="emptyCart">
                <a class="iconX closeDlg"></a>
                {t}В корзине нет товаров{/t}
            </div>            
            {/if}
        </div>
    </div>
{else}
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
            {hook name="shop-cartpage:products" title="{t}Корзина:товары{/t}"}
                <div class="scrollCartWrap">
                <table class="cartTable">
                    <tbody>
                    {foreach $cart_data.items as $index => $item}
                        {$product=$product_items[$index].product}
                        {$cartitem=$product_items[$index].cartitem}
                        {if !empty($cartitem.multioffers)}
                               {$multioffers=unserialize($cartitem.multioffers)} 
                        {/if}                    
                        <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem{if $smarty.foreach.items.first} first{/if}">
                            <td class="image">
                                {$main_image=$product->getMainImage()}
                                <a href="{$product->getUrl()}"><img src="{$main_image->getUrl(100,100)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
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

                                <p class="desc">{$product.short_description}</p>
                            </td>
                            <td class="amount">
                                <input type="number" min="{$product->getAmountStep()}" step="{$product->getAmountStep()}" class="inp fieldAmount" value="{$cartitem.amount}" name="products[{$index}][amount]">
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
                        <tr class="coupons" data-id="{$id}">
                            <td class="image"></td>
                            <td class="title">{t}Купон на скидку{/t} {$item.coupon.code}</td>
                            <td class="amount"></td>
                            <td class="price"></td>
                            <td class="remove">
                                <a title="{t}Удалить скидочный купон из корзины{/t}" class="iconX" href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $id])}"></a>
                            </td>
                        </tr>
                    {/foreach}           
                    </tbody>
                </table>
            {/hook}
            
            {hook name="shop-cartpage:summary" title="{t}Корзина:итог{/t}"}
                <div class="cartTableAfter">
                    <span class="mobileWrapper">
                        <span class="cap">{t}Купон на скидку(если есть){/t} </span>
                        <input type="text" class="couponCode{if $cart->getUserError('coupon')!==false} hasError{/if}" name="coupon" value="{$coupon_code}"> 
                        <a data-href="{$router->getUrl('shop-front-cartpage', ["Act" => "applyCoupon"])}" class="applyCoupon">{t}Применить{/t}</a>
                    </span>
                    <p class="price"><span class="text">{t}Итого{/t}:</span>{$cart_data.total}</p>
                    <div class="loader"></div>
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
                    <a href="{$router->getRootUrl(true)}" class="button continue">{t}Продолжить покупки{/t}</a>
                    <a href="{$router->getUrl('shop-front-checkout')}" class="submit colorButton{if $cart_data.has_error} disabled{/if}">{t}Оформить заказ{/t}</a>
                    
                    {if $THEME_SETTINGS.enable_one_click_cart}
                        <a href="JavaScript:;" class="button toggleOneClickCart">{t}Заказать по телефону{/t}</a>
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
            {t}В корзине нет товаров{/t}
        </div>                    
        {/if}
    </div>
{/if}