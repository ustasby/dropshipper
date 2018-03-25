{if $category && $news}
        <ul class="news">
            {foreach $news as $item}
            <li {$item->getDebugAttributes()}>
                <p class="date">{$item.dateof|dateformat:"%d %v %Y, %H:%M"}</p>
                <a href="{$item->getUrl()}" class="title">{$item.title}</a>
                <p>{$item->getPreview()}</p>
            </li>
            {/foreach}
        </ul>
        <div class="textCenter">
            <a href="{$router->getUrl('article-front-previewlist', [category => $category->getUrlId()])}" class="colorButton">{t}Все новости{/t}</a>
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