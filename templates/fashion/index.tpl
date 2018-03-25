{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
    {* Баннеры *}
    {moduleinsert name="\Banners\Controller\Block\Slider" zone="fashion-center"}

    <div class="box mt40">
        {* Лидеры продаж *}
        {moduleinsert name="\Catalog\Controller\Block\TopProducts" dirs="samye-prodavaemye-veshchi" pageSize="5"}
             
        <div class="oh mt40">
            <div class="left">
                {* Новости *}
                {moduleinsert name="\Article\Controller\Block\LastNews" indexTemplate="blocks/lastnews/lastnews.tpl" category="2" pageSize="4"}
            </div>
            <div class="right">
                {* Оплата и возврат *}
                {moduleinsert name="\Article\Controller\Block\Article" indexTemplate="blocks/article/main_payment_block.tpl" article_id="molodezhnaya--glavnaya--ob-oplate"}
                
                {* Доставка *}
                {moduleinsert name="\Article\Controller\Block\Article" indexTemplate="blocks/article/main_delivery_block.tpl" article_id="molodezhnaya--glavnaya--o-dostavke"}
            </div>
        </div>
        {* Товары во вкладках *}
        {moduleinsert name="\Catalog\Controller\Block\ProductTabs" categories=["populyarnye-veshchi", "novye-postupleniya"] pageSize=6}
        
        {* Бренды *}
        {moduleinsert name="\Catalog\Controller\Block\BrandList"}
    </div>
{/block}