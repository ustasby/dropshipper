{if $dirs}
{$shop_config=ConfigLoader::byModule('shop')}
<div class="tabs mt40 tabProducts">
    <ul class="tabList">
        {foreach $dirs as $dir}
        <li {if $dir@first}class="act"{/if} data-href=".frame{$dir@iteration}"><a>{$dir.name}</a></li>
        {/foreach}
    </ul>
    {foreach $dirs as $dir}
    <div class="tab {if $dir@first}act{/if} frame{$dir@iteration}">
        <ul class="products">
            {foreach $products_by_dirs[$dir.id] as $product}
            <li {$product->getDebugAttributes()} data-id="{$product.id}">
                {$main_image=$product->getMainImage()}
                <a href="{$product->getUrl()}" class="image">{if $product->inDir('new')}<i class="new"></i>{/if}<img src="{$main_image->getUrl(188,258)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
                <a href="{$product->getUrl()}" class="title">{$product.title}</a>
                {$last_price=$product->getOldCost()}
                {if $last_price>0}<p class="lastPrice">{$last_price} {$product->getCurrency()}</p>{/if}
                <p class="price">{$product->getCost()} {$product->getCurrency()}</p>
            </li>                                          
            {/foreach}
        </ul>
        <a href="{$dir->getUrl()}">Посмотреть все товары</a>
    </div>
    {/foreach}
</div>
{else}
    {include file="%THEME%/block_stub.tpl"  class="blockProductTabs" do=[
        [
            'title' => t("Добавьте категории с товарами"),
            'href' => {adminUrl do=false mod_controller="catalog-ctrl"}
        ],
        [
            'title' => t("Настройте блок"),
            'href' => {$this_controller->getSettingUrl()},
            'class' => 'crud-add'
        ]
    ]}
{/if}