{if count($products)}
<div class="topProduct bmBig">
    {$main_image=$products.0->getMainImage()}
    <a href="{$products.0->getUrl()}" class="image"><img src="{$main_image->getUrl(127,127)}" alt="{$main_image.title|default:"{$products.0.title}"}"></a>
    <div class="info">
        <p class="h3">{$dir.name}</p>
        <a href="{$products.0->getUrl()}" class="title">{$products.0.title}</a>
        <p class="price"><strong>{$products.0->getCost()} {$products.0->getCurrency()}</strong></p>
    </div>
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