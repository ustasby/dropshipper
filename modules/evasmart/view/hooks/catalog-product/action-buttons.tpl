{$shop_config = ConfigLoader::byModule('shop')}
{$new_cost=$product->getCost()}

{if $new_cost}
    {if $THEME_SETTINGS.enable_amount_in_product_card}
        <div class="page-product_quantity rs-product-amount">
            <div class="quantity-text">{t}Кол-во:{/t}</div>
            <div class="quantity">
                <input type="number" step="{$product->getAmountStep()}" value="{$product->getAmountStep()}" name="amount" class="rs-field-amount">
                <div class="quantity-nav rs-unit-block">
                    <div class="quantity-unit rs-unit">
                        {if $catalog_config.use_offer_unit}
                            {$product.offers.items[0]->getUnit()->stitle}
                        {else}
                            {$product->getUnit()->stitle}
                        {/if}
                    </div>
                    <div class="quantity-button quantity-up rs-inc" data-amount-step="{$product->getAmountStep()}">+</div>
                    <div class="quantity-button quantity-down rs-dec" data-amount-step="{$product->getAmountStep()}">-</div>
                </div>
            </div>
        </div>
    {/if}
{else}
    <span class="rs-unobtainable" style="display: block">{t}Для просмотра цен требуется регистрация{/t}</span>
{/if}

{if $shop_config}
    {*
    <a data-url="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="link link-one-click rs-reserve rs-in-dialog">{t}Заказать{/t}</a>

    <span class="rs-unobtainable">{t}Нет в наличии{/t}</span>
    *}
    {if $product->getCost()}
    <a data-url="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="link link-more rs-to-cart" data-add-text="{t}Добавлено{/t}">{t}В корзину{/t}</a>
    {/if}
{/if}

{if !$shop_config || (!$product->shouldReserve() && (!$check_quantity || $product.num>0))}
    <link itemprop="availability" href="http://schema.org/InStock"> {* Товар есть в наличии *}
    {if $catalog_config.buyinoneclick }
        <a data-url="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id])}" title="{t}Купить в 1 клик{/t}" class="link link-one-click rs-buy-one-click rs-in-dialog">{t}Купить в 1 клик{/t}</a>
    {/if}
{/if}


