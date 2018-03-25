{if $category && $news}
<h3><a href="{$router->getUrl('article-front-previewlist', [category => $category->getUrlId()])}">Новости</a></h3>
<ul class="news">
    {foreach $news as $item}
    <li {$item->getDebugAttributes()}>
        <p class="date">{$item.dateof|dateformat:"%d %v %Y, %H:%M"}</p>
        <a href="{$item->getUrl()}" class="descr">{$item.title}</a>
    </li>
    {/foreach}
</ul>
{else}
    {include file="%THEME%/block_stub.tpl"  class="blockLastNews" do=[
        [
            'title' => t("Добавьте категорию с новостями"),
            'href' => {adminUrl do=false mod_controller="article-ctrl"}
        ],        
        [
            'title' => t("Настройте блок"),
            'href' => {$this_controller->getSettingUrl()},
            'class' => 'crud-add'
        ]        
    ]}
{/if}