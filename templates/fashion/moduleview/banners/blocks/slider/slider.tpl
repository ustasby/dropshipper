{addjs file="{$mod_js}jquery.photoslider.js" basepath="root"}
{if $zone}
    {$banners=$zone->getBanners()}
    <div class="bannerSlider">
        <ul class="items">
            {foreach $banners as $banner}
            <li {$banner->getDebugAttributes()} class="item{if $banner@first} act{/if}">
                {if $banner.link}<a href="{$banner.link}"{if $banner.targetblank}target="_blank"{/if}>{/if} 
                <img src="{$banner->getBannerUrl($zone.width, $zone.height, 'axy')}" alt="{$banner.title}">
                {if $banner.link}</a>{/if}
            </li>
            {/foreach}            
        </ul>
        {if count($banners)>1}
        <div class="pages">
            <span class="center">
                <span class="back"></span>
                <span class="list">        
                {foreach $banners as $banner}
                    <a {if $banner@first}class="act"{/if}>{$banner@iteration}</a>
                {/foreach}
                </span>
            </span>
        </div>
        {/if}
    </div>
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