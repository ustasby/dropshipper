{if !empty($products)}
    <section>
        {foreach from=$products item=product}
            <div class="topProduct bmBig">
                {$main_image=$product->getMainImage()}
                <a href="{$product->getUrl()}" class="image"><img src="{$main_image->getUrl(127,127)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
                <div class="info">
                    <p class="h3">{$product->getMainDir()->name}</p>
                    <a href="{$product->getUrl()}" class="title">{$product.title}</a>
                    <p class="price"><strong>{$product->getCost()} {$product->getCurrency()}</strong></p>
                </div>
            </div>
        {/foreach}
    </section>
{/if}