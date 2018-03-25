{if !empty($brands)}
    <div class="brandLine">
        <p class="blockTitle"><span class="top">{t}Бренды{/t}</span></p>
            <ul> 
                {foreach $brands as $brand}
                    {if $brand.image}
                        <li {$brand->getDebugAttributes()}>
                            <a href="{$brand->getUrl()}">
                                <img src="{$brand->__image->getUrl(130,100,'axy')}" alt="{$brand.title}"/>
                            </a>
                        </li>
                    {/if}
                {/foreach}
            </ul>
       </div>
{/if}