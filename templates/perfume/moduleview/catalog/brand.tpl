<div class="brandPage">
    <h1>{$brand.title}</h1>
    <article class="description">
       {if $brand.image}<img src="{$brand->__image->getUrl(250,250,'xy')}" class="mainImage" alt="{$brand.title}"/>{/if}
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
            <h2>Категории товаров {$brand.title}</h2>
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
        <div class="productList rsTabs">
                <ul class="tabList">
                    <li class="act" data-href=".frame1"><a>{t}Актуальные товары{/t} {$brand.title}</a></li>
                </ul>       
              <div class="catalog tab act frame1">
                  <ul class="products">  
                      {foreach $products as $product}
                            {include file="%catalog%/one_product.tpl" shop_config=$shop_config}
                      {/foreach}
                  </ul>
              </div>
          </div>
    {/if}   
</div>