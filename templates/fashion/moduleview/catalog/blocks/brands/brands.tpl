{addjs file="jcarousel/jquery.jcarousel.min.js"}
{if !empty($brands)}
    <div class="brandLine">
        <div class="gallery">
            <ul> 
                {foreach $brands as $brand}
                    {if $brand.image}
                        <li {$brand->getDebugAttributes()}>
                            <a href="{$brand->getUrl()}">
                                <img src="{$brand->__image->getUrl(100,100,'axy')}" alt="{$brand.title}"/>
                            </a>
                        </li>
                    {/if}
                {/foreach}
            </ul>
        </div>
        <a class="brandcontrol prev"></a>
        <a class="brandcontrol next"></a>  
    </div>
    <div class="brandall">
        <a href="{$router->getUrl('catalog-front-allbrands')}">Все бренды</a>
    </div> 
   
    <script type="text/javascript">
        $(function() {
            $('.brandLine .gallery').jcarousel();
            $('.brandcontrol').on({
                'inactive.jcarouselcontrol': function() {
                    $(this).addClass('disabled');
                },
                'active.jcarouselcontrol': function() {
                    $(this).removeClass('disabled');
                }
            });
            $('.brandcontrol.prev').jcarouselControl({ target: '-=2' });
            $('.brandcontrol.next').jcarouselControl({ target: '+=2' });
        });
    </script>
{/if}