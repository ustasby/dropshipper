{if $dirs}
{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config.check_quantity}
<div class="productList rsTabs">
    <ul class="tabList">
        {foreach $dirs as $dir}
        <li {if $dir@first}class="act"{/if} data-href=".frame{$dir@iteration}"><a>{$dir.name}</a></li>
        {/foreach}
    </ul>
    {foreach $dirs as $dir}
    <div class="tab {if $dir@first}act{/if} frame{$dir@iteration}">
        <div class="tabCaption"><span class="sel">{$dir.name}</span></div>
        <div class="scrollBlock">
            <ul class="scrollItems products">
                {$products_by_dirs[$dir.id]=$this_controller->api->addProductsMultiOffersInfo($products_by_dirs[$dir.id])}
                {foreach $products_by_dirs[$dir.id] as $product}
                    {include file="%catalog%/one_product.tpl" shop_config=$shop_config product=$product}
                {/foreach}
            </ul>
        </div>
    </div>
    {/foreach}
    <br class="clear">    
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