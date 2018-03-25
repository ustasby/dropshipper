{* Список категорий из 3-х уровней*}
{if $dirlist}
<div class="mobile">
    <a class="caption ht">{t}Категории{/t}</a>
    <a href="#" class="ht toggle"></a>
</div>
<ul class="catalog">
    {hook name="catalog-blocks-category-category:list-item" title="{t}Доплнительные пункты меню, в меню каталога{/t}"}
    {foreach $dirlist as $dir}
    <li class="{if !empty($dir.child)} node{/if}" {$dir.fields->getDebugAttributes()}>
        <a href="{$dir.fields->getUrl()}">{$dir.fields.name}</a><i></i>
        {if !empty($dir.child)}
            {$cnt=count($dir.child)}
            {$columns=1}
            {if $cnt>3}{$columns=2}{/if}
            {if $cnt>6}{$columns=3}{/if}
            {if $cnt>12}{$columns=4}{/if}
            {* Второй уровень *}
            <ul class="columns{$columns}">
                {foreach $dir.child as $subdir}
                <li {if !empty($subdir.child)}class="node"{/if}><a href="{$subdir.fields->getUrl()}">{$subdir.fields.name}</a>
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
<script type="text/javascript">
    $(function() {
        $('.catalog .node > a, .catalog .node > i').click(function(e) {
            if ($.detectMedia('mobile') || $.detectMedia('portrait')) {
                $(this).closest('.node').toggleClass('open');
                e.preventDefault();
            }
        });
        $('.topCategory .ht').click(function() {
            $('.topCategory').toggleClass('open');
            return false;
        });        

    });
</script>
{else}
    {include file="%THEME%/block_stub.tpl"  class="blockCategory blockSmall" do=[
        [
            'title' => t("Добавьте категории товаров"),
            'href' => {adminUrl do=false mod_controller="catalog-ctrl"}
        ]
    ]}
{/if}