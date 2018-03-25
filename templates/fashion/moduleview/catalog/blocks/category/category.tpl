{* Список категорий из 2-х уровней*}
{if $dirlist}
<nav class="category">
    <ul>
        {hook name="catalog-blocks-category-category:list-item" title="{t}Доплнительные пункты меню, в меню каталога{/t}"}
        {foreach $dirlist as $dir}
        <li class="{if !empty($dir.child)} node{/if}" {$dir.fields->getDebugAttributes()}>
            <a href="{$dir.fields->getUrl()}">{$dir.fields.name}</a><i></i>
            {if !empty($dir.child)}
                {* Второй уровень *}
                <ul>
                    {foreach $dir.child as $subdir}
                    <li><a href="{$subdir.fields->getUrl()}">{$subdir.fields.name}</a>
                        {if !empty($subdir.child)}
                        {* Третий уровень *}
                        <ul>
                            {foreach $subdir.child as $subdir2}
                            <li><a href="{$subdir2.fields->getUrl()}">{$subdir2.fields.name}</a></li>
                            {/foreach}
                        </ul>
                        {/if}
                    </li>
                    {/foreach}
                </ul>
            {/if}
        </li>
        {/foreach}
        {/hook}
    </ul>
</nav>
{else}
    {include file="%THEME%/block_stub.tpl"  class="blockCategory" do=[
        [
            'title' => t("Добавьте категории товаров"),
            'href' => {adminUrl do=false mod_controller="catalog-ctrl"}
        ]
    ]}
{/if}