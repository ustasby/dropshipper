{addjs file="{$mod_js}jquery.photoslider.js" basepath="root"}
{if $zone}
    {$banners=$zone->getBanners()}
    <div class="bannerSlider">
        <ul class="banners">
            {foreach $banners as $banner}
            <li {$banner->getDebugAttributes()} class="item{if $banner@first} act{/if}">
                <div class="centerContainer">
                    <div class="centerBlock">
                        {if $banner.link}<a href="{$banner.link}" {if $banner.targetblank}target="_blank"{/if}>{/if}<img src="{$banner->getBannerUrl($zone.width, $zone.height, 'axy')}" alt="{$banner.title}">{if $banner.link}</a>{/if}
                    </div>
                </div>
            </li>
            {/foreach}            
        </ul>
        <div class="pages">
            {foreach $banners as $banner}<a class="i_{$banner@iteration} {if $banner@first}act{/if}">{$banner@iteration}</a>{/foreach}
        </div>
    </div>
    <script type="text/javascript">
        $(window).load(function() {
            var resize = function() {
                $('.bannerSlider').height( $('.bannerSlider > ul > li:first').height() );
            }
            resize();
            $(window).resize(resize);
        })
    </script>
{else}
    {include file="%THEME%/block_stub.tpl"  class="blockSlider" do=[
        [
            'title' => t("Добавьте зону с баннерами"),
            'href' => {adminUrl do=false mod_controller="banners-ctrl"}
        ],
        [
            'title' => t("Настройте блок"),
            'href' => {$this_controller->getSettingUrl()},
            'class' => 'crud-add'
        ]
    ]}
{/if}