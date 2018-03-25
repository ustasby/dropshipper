{if $products}
<div class="leaders">
    <h3>{$dir.name}</h3>
    <ul class="products">
    {foreach $products as $product}
        <li {$product->getDebugAttributes()} data-id="{$product.id}">
            {$main_image=$product->getMainImage()}
            <a href="{$product->getUrl()}" class="image">{if $product->inDir('new')}<i class="new"></i>{/if}<img src="{$main_image->getUrl(188,258)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
            <a href="{$product->getUrl()}" class="title">{$product.title}</a>
            <p class="price">{$product->getCost()} {$product->getCurrency()} 
                {$last_price=$product->getOldCost()}
                {if $last_price>0}<span class="last">{$last_price} {$product->getCurrency()}</span>{/if}</p>
        </li>                   
    {/foreach}    
    </ul>
</div>
{else}
    {include file="%THEME%/block_stub.tpl"  class="blockTopProducts" do=[
        [
            'title' => t("Добавьте категорию с товарами"),
            'href' => {adminUrl do=false mod_controller="catalog-ctrl"}
        ],
        [
            'title' => t("Настройте блок"),
            'href' => {$this_controller->getSettingUrl()},
            'class' => 'crud-add'
        ]
    ]}
{/if}