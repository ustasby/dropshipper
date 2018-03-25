{if $site_config.logo}
    <div class="logo">
        {if $link != ' '}<a href="{$link}">{/if}
        <img src="{$site_config.__logo->getUrl($width, $height)}" alt=""/>
        {if $link != ' '}</a>{/if}
        <p class="slogan">{$site_config.slogan}</p>
    </div>
{else}
    {include file="%THEME%/block_stub.tpl"  class="noBack blockSmall blockLeft blockLogo" do=[
        {adminUrl do=false mod_controller="site-options"}    => t("Добавьте логотип")
    ]}
{/if}