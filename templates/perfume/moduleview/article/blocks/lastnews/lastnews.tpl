{if $category && $news}
    <div class="newsBlock">
        <h3>{t}Новости{/t}</h3>
        <ul class="news">
            {foreach $news as $item}
            <li class="item {if $item@iteration % 2==0}fr{else}fl{/if}" {$item->getDebugAttributes()}>
                <p class="date">{$item.dateof|dateformat:"%d %v %Y, %H:%M"}</p>
                <a href="{$item->getUrl()}" class="title">{$item.title}</a>
                <p class="descr">{$item->getPreview()}</p>
            </li>
            {/foreach}
        </ul>
        <a href="{$router->getUrl('article-front-previewlist', [category => $category->getUrlId()])}" class="more">{t}Все новости{/t}</a>
    </div>
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