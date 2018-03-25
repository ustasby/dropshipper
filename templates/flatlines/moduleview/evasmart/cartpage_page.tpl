{* Корзина на отдельной странице *}
{addjs file="evasmart.ds_checkout.js"}


{$shop_config=ConfigLoader::byModule('shop')}
{$catalog_config=ConfigLoader::byModule('catalog')}
{$product_items=$cart->getProductItems()}
{$cart_items = $cart->getItems()}
<div class="page-basket" id="rs-cart-items">
    <div class="page-basket_wrapper">
        {if !empty($cart_data.items)}
            <form id="rs-ds-cart-form" class="row" method="POST"
                  action="{$router->getUrl('evasmart-front-cartpage', ["Act" => "update", "floatCart" => $floatCart])}">
                <div class="col-xs-12 col-md-9">

                    {hook name="evasmart-cartpage:products" title="{t}Корзина:товары{/t}" product_items=$product_items}
                        <div class="catalog-list">
                            <div class="row">
                                <!-- {*$cart_items|print_r:1*} -->
                                {foreach $cart_data.items as $index => $item}
                                    {$product = $product_items[$index].product}
                                    {$cartitem = $product_items[$index].cartitem}

                                    {if !empty($cartitem.multioffers)}
                                        {$multioffers=unserialize($cartitem.multioffers)}
                                    {/if}

                                    {if !empty($cartitem.extra)}
                                        {$extra=unserialize($cartitem.extra)}
                                        {$mat=str_replace(',', '<br>', $extra.additional_uniq)}
                                    {/if}
                                    <div class="col-xs-12 rs-cartitem" data-id="{$index}" data-product-id="{$cartitem.entity_id}">
                                        <input type="hidden" name="id[{$index}]" value="{$index}">
                                        <input type="hidden" name="product_id[{$index}]" value="{$cartitem.entity_id}">
                                        <div class="card card-product">
                                            <div class="card-image">
                                                <a href="{$product->getUrl()}"><img src="{$product->getOfferMainImage($cartitem.offer, 476, 280)}" alt="{$product.title}"></a>
                                            </div>

                                            <div class="card-text">
                                                <div class="card-product_category-name">
                                                    <a href="{$product->getMainDir()->getUrl()}"><small>{$product->getMainDir()->name}</small></a>
                                                </div>

                                                <div class="card-product_title">
                                                    <a href="{$product->getUrl()}"><span>{$cartitem.title}</span></a>

                                                    <div class="card-product_quantity">
                                                        {$offer_barcode=$product->getBarcode($cartitem.offer)}
                                                        {if $offer_barcode}
                                                            <p class="barcode">{t}Артикул{/t}: {$product->getBarcode($cartitem.offer)}</p>
                                                        {elseif $product.barcode}
                                                            <p class="barcode">{t}Артикул{/t}: {$product.barcode}</p>
                                                        {/if}
                                                    </div>


                                                    {if $product->isMultiOffersUse()}
                                                        <div class="card-product_multi-offers multioffer">

                                                            {foreach $product.multioffers.levels as $level}
                                                                {if !empty($level.values)}
                                                                    <div class="multioffer_title">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</div>
                                                                    <select class="select" name="products[{$index}][multioffers][{$level.prop_id}]" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}">
                                                                        {foreach $level.values as $value}
                                                                            <option {if $multioffers[$level.prop_id].value == $value.val_str}selected="selected"{/if} value="{$value.val_str}">{$value.val_str}</option>
                                                                        {/foreach}
                                                                    </select>
                                                                {/if}
                                                            {/foreach}

                                                            {if $product->isOffersUse()}
                                                                {foreach $product.offers.items as $key => $offer}
                                                                    <input id="offer_{$key}" type="hidden" name="hidden_offers" class="hidden_offers" value="{$key}" data-info='{$offer->getPropertiesJson()}' data-num="{$offer.num}"/>
                                                                    {if $cartitem.offer==$key}
                                                                        <input type="hidden" name="products[{$index}][offer]" value="{$key}"/>
                                                                    {/if}
                                                                {/foreach}
                                                            {/if}
                                                        </div>
                                                    {elseif $product->isOffersUse()}

                                                        <div class="card-product_offers">
                                                            {foreach $product.offers.items as $key => $offer}
                                                                {if $cartitem.offer==$key}<span>{$offer.title}</span>{/if}
                                                            {/foreach}
                                                        </div>
                                                        {if $mat}{$mat}{/if}
                                                    {/if}
                                                </div>

                                                <div class="card-product_quantity">
                                                    <div class="quantity rs-amount">
                                                        <input type="number" value="{$cartitem.amount}" class="rs-field-amount">
                                                        <div class="quantity-nav">
                                                            <div class="quantity-unit">
                                                                {if $catalog_config.use_offer_unit}
                                                                    {$product.offers.items[$cartitem.offer]->getUnit()->stitle}
                                                                {else}
                                                                    {$product->getUnit()->stitle}
                                                                {/if}
                                                            </div>

                                                        </div>
                                                    </div>
                                                    <div class="error">{$item.amount_error}</div>
                                                </div>
                                            </div>

                                            <div class="card-price">
                                                <div class="card-price_discount">Цена для покупателя
                                                <input class="js-ds-cost" name="ds_single_cost[{$index}]" type="number" value="{$cart_items.$index.ds_single_cost}">
                                                </div>
                                                <span class="card-price_present">{$item.single_cost}</span>
                                                <span class="card-price_present">Итого: {$item.cost}</span>
                                                <div class="card-price_discount">
                                                    {if $item.discount>0}
                                                        {t discount=$item.discount}скидка %discount{/t}
                                                    {/if}
                                                </div>
                                                <div>
                                                    {*<a href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $index])}"
                                                       class="link link-del rs-remove"><i class="pe-2x pe-7s-close"></i> {t}Удалить{/t}</a>*}
                                                </div>
                                            </div>

                                        </div>


                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    {/hook}

                </div>
                <div class="col-xs-12 col-md-3 sticky">
                    <div class="sidebar sticky">
                        <div class="sidebar_blocks">

                            {hook name="evasmart-cartpage:summary" title="{t}Корзина:итог{/t}"}
                                <div class="t-order-total">
                                    <div class="t-order-total_wrapper">
                                        <p>{t}Сумма{/t}:</p>
                                        <div class="t-order-total_price"><span>{$cart_data.total}</span></div>
                                    </div>
                                    {*<a class="theme-btn_reset rs-clear-cart"
    href="{$router->getUrl('shop-front-cartpage', ["Act" => "cleanCart", "floatCart" => $floatCart])}">
    <i class="pe-7s-close-circle"></i> {t}Очистить корзину{/t}</a>*}
                                </div>
                            {/hook}


                            {hook name="evasmart-cartpage:bottom" title="{t}Корзина:подвал{/t}"}
                                <div class="t-order-checkout">
                                    <div class="t-order-errors js-ds-error hidden">

                                    </div>
                                    {if !empty($cart_data.errors)}
                                        <div class="t-order-errors">
                                            {foreach $cart_data.errors as $error}
                                                {$error}<br>
                                            {/foreach}
                                        </div>
                                    {/if}

                                    <div class="t-order_button-block">

                                        <button type="submit" class="link link-more link-apply {if $cart_data.has_error} disabled{/if}">{t}Оформить заказ{/t}</button>
                                        {*<button type="button" class="link link-more link-apply rs-submit{if $cart_data.has_error} disabled{/if}">{t}Оформить заказ{/t}</button>*}
                                        <a class="link link-one-click rs-continue">{t}Продолжить покупки{/t}</a>
                                    </div>
                                </div>
                            {/hook}

                        </div>
                    </div>
                </div>
            </form>
        {else}
            <div class="empty-list">
                {t}В корзине нет товаров{/t}
            </div>
        {/if}
    </div>
</div>