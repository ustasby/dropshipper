{if $THEME_SHADE != 'pink'}
    {$SHADE="{$THEME_SHADE}/"}
{/if}
<li {$product->getDebugAttributes()} class="viewContainer">
    <div class="titleBlock">
        <a href="{$product->getUrl()}" class="title">{$product.title}</a>                    
    </div>
    <div class="imageBlock">
        {$main_image=$product->getMainImage()}
        <a href="{$product->getUrl()}" class="image"><img src="{$main_image->getUrl(425, 360)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
    </div>
    <div class="info">
        <div class="price">                                
            <div class="value">{t}Цена{/t}<br>{$product->getCost()} {$product->getCurrency()}</div>
        </div>
    </div>
    {if $item>0}<a class="prev" data-params='{ "dir":"{$dir}", "item":"{$item-1}"}'></a>{/if}
    {if $item<$total-1}<a class="next" data-params='{ "dir":"{$dir}", "item":"{$item+1}"}'></a>{/if}
</li>