{if !empty($recommended)}
<div class="block recommended">
<h4 class="bordered">{$recommended_title|default:t("С этим товаром покупают")}</h4>    <ul>
        {foreach $recommended as $product}
        <li>
            {$main_image=$product->getMainImage()}
            <a href="{$product->getUrl()}" class="image"><img src="{$main_image->getUrl(80, 76)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
            <div class="info">
                <a href="{$product->getUrl()}" class="title">{$product.title}</a>
                <p class="price">{$product->getCost()} {$product->getCurrency()}</p>
            </div>
        </li>
        {/foreach}
    </ul>
</div>
{/if}