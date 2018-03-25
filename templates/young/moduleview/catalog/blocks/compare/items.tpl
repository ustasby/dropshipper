{foreach $list as $product}
<li data-compare-id="{$product.id}">
    <a title="{t}Исключить из сравнения{/t}" class="remove"></a>
    <a href="{$product->getUrl()}" class="title">{$product.title}</a>
</li>
{/foreach}