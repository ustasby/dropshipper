{if $site_config.logo}
    {if $link != ' '}<a href="{$link}" class="logo">{/if}
    <img src="{$site_config.__logo->getUrl($width, $height)}" alt=""/>
    {if $link != ' '}</a>{/if}
    <div class="slogan">{$site_config.slogan}</div>
{else}
    {include file="%THEME%/block_stub.tpl"  class="noBack blockSmall blockLeft blockLogo" do=[
        {adminUrl do=false mod_controller="site-options"}    => t("Добавьте логотип")
    ]}
{/if}