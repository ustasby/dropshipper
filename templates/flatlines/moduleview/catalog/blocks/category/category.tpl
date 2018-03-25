{* Список категорий из 3-х уровней *}
{nocache}
{addjs file="libs/jquery.mmenu.min.js"}
{addcss file="libs/jquery.mmenu.css"}
{/nocache}

{if $dirlist}
<nav>
    <ul class="nav navbar-nav">
        {hook name="catalog-blocks-category-category:list-item" title="{t}Доплнительные пункты меню, в меню каталога{/t}"}
        {foreach $dirlist as $dir}
        <li class="{if !empty($dir.child)} t-dropdown{/if}" {$dir.fields->getDebugAttributes()}>
            {* Первый уровень *}
            <a {$dir.fields->getDebugAttributes()} href="{$dir.fields->getUrl()}">{$dir.fields.name}</a>

            {if !empty($dir.child)}
                {* Второй уровень *}
                <div class="t-dropdown-menu">
                    <div class="container-fluid">
                        <div class="t-nav-catalog-list__inner">
                            <div class="t-close"><i class="pe-2x pe-7s-close-circle"></i></div>
                            <div class="t-nav-catalog-list__scene">

                                {foreach $dir.child as $subdir}
                                    <div class="t-nav-catalog-list-block">
                                        <a {$subdir.fields->getDebugAttributes()} href="{$subdir.fields->getUrl()}" class="t-nav-catalog-list-block__header">{$subdir.fields.name}</a>

                                        {* Третий уровень *}
                                        {if !empty($subdir.child)}
                                        <ul class="t-nav-catalog-list-block__list">
                                            {foreach $subdir.child as $subdir2}
                                                <li><a {$subdir2.fields->getDebugAttributes()} href="{$subdir2.fields->getUrl()}" class="t-nav-catalog-list-block__link">{$subdir2.fields.name}</a></li>
                                            {/foreach}
                                        </ul>
                                        {/if}
                                    </div>
                                {/foreach}

                        </div>
                    </div>
                </div>
                </div>
            {/if}
        </li>
        {/foreach}
        {/hook}
    </ul>
</nav>


{* Мобильная версия каталога - 2 уровня *}
<nav id="mmenu" class="hidden">
    <ul>
        <li>
            {moduleinsert name="\Catalog\Controller\Block\SearchLine" hideAutoComplete=true}
        </li>
        {hook name="catalog-blocks-category-category:list-item-mobile" title="{t}Доплнительные пункты меню, в меню каталога - мобильная версия{/t}"}
        {foreach $dirlist as $dir}
            <li>
                <a href="{$dir.fields->getUrl()}">{$dir.fields.name}</a>
                {if !empty($dir.child)}
                    <ul>
                        {foreach $dir.child as $subdir}
                            <li>
                                <a href="{$subdir.fields->getUrl()}">{$subdir.fields.name}</a>
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
    <div class="col-padding">
        {include file="%THEME%/block_stub.tpl"  class="text-center white block-category" do=[
            [
                'title' => t("Добавьте категории товаров"),
                'href' => {adminUrl do=false mod_controller="catalog-ctrl"}
            ]
        ]}
    </div>
{/if}