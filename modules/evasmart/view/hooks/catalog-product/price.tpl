{$new_cost=$product->getCost()}
{if $old_cost = $product->getOldCost()}
    {if $old_cost != $new_cost}
        <span class="card-price_old"><span class="rs-price-old">{$old_cost}</span> <span class="card-price_currency">{$product->getCurrency()}</span></span>
    {/if}
{/if}
<span class="card-price_new">

    {if $new_cost}
        <span itemprop="price" class="rs-price-new  myCost" content="{$product->getCost(null, null, false)}">{$new_cost}</span>
        <span class="card-price_currency ">{$product->getCurrency()}</span>
        <meta itemprop="priceCurrency" content="{$product->getCurrencyCode()}">
        {* Если включена опция единицы измерения в комплектациях *}
        {if $catalog_config.use_offer_unit && $product->isOffersUse()}
            <span class="rs-unit-block">/ <span class="rs-unit">{$product.offers.items[0]->getUnit()->stitle}</span></span>
        {/if}
    {else}

        <meta itemprop="price" content="{$product->getCost(null, null, false)}">
        <meta itemprop="priceCurrency" content="{$product->getCurrencyCode()}">
    {/if}
</span>