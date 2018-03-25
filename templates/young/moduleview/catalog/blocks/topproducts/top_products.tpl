{if $products}
{$shop_config=ConfigLoader::byModule('shop')}
{$products = $this_controller->api->addProductsMultiOffersInfo($products)}

<ul class="products">
    {foreach $products as $product}
        {include file="%catalog%/one_product.tpl" shop_config=$shop_config product=$product}
    {/foreach}    
</ul>
<div class="clearLeft"></div>
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