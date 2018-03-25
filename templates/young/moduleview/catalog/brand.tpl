<div class="brandPage">
    <article class="description">    
       {if $brand.image}<div class="imageWrap"><img src="{$brand->__image->getUrl(250,250,'xy')}" alt="{$brand.title}"/></div>{/if}
       {$brand.description}
    </article>
    
    {if !empty($dirs)}
        {if count($dirs) < 6}
        {elseif count($dirs) < 15}
            {$widthClass="col2"}
        {else}
            {$widthClass="col3"}
        {/if}
    
        <div class="brandDirs">
            <h2>{t}Категории товаров{/t} {$brand.title}</h2>
            <ul class="cats {$widthClass}">
             {foreach $dirs as $dir}
                <li>
                    <a href="{$router->getUrl('catalog-front-listproducts',['category'=>$dir._alias,'bfilter'=> ["brand" => [$brand.id]]])}">{$dir.name}</a> <sup>({$dir.brands_cnt})</sup>
                </li>
             {/foreach}
            </ul>
        </div>
    {/if}
    
    {if !empty($products)}
        {$shop_config=ConfigLoader::byModule('shop')}
        {$products = $this_controller->api->addProductsMultiOffersInfo($products)}
            
        <h2>{t}Актуальные товары{/t} {$brand.title}</h2>
        <ul class="products">
            {foreach $products as $product}
                {include file="%catalog%/one_product.tpl" shop_config=$shop_config product=$product}
            {/foreach}    
        </ul>
        <br class="clearboth">
    {/if}   
</div>