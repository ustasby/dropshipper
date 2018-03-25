{* Список категорий из 3-х уровней*}
{if $dirlist}
<ul class="topCategory" id="topCategory">
    {hook name="catalog-blocks-category-category:list-item" title="{t}Доплнительные пункты меню, в меню каталога{/t}"}
    {foreach $dirlist as $dir}
    <li class="item_{$dir@iteration}{if !empty($dir.child)} node{/if}" {$dir.fields->getDebugAttributes()}><a class="{if !empty($dir.child)}dirChild{/if}" href="{$dir.fields->getUrl()}">{$dir.fields.name}</a>
        {if !empty($dir.child)}
            {$cnt=count($dir.child)}
            {$columns=1}
            {if $cnt>3}{$columns=2}{/if}
            {if $cnt>6}{$columns=3}{/if}
            {if $cnt>12}{$columns=4}{/if}
            {* Второй уровень *}
            <ul class="columns{$columns}">
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

{else}
    {include file="%THEME%/block_stub.tpl"  class="blockCategory" do=[
        [
            'title' => t("Добавьте категории товаров"),
            'href' => {adminUrl do=false mod_controller="catalog-ctrl"}
        ]
    ]}
{/if}