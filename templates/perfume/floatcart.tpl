{$wrapped_content}
<div class="fixedCart">
    <div class="container_12">
        <a href="#" class="up" id="up" title="{t}наверх{/t}"></a>
        {if ModuleManager::staticModuleExists('shop')}
        {moduleinsert name="\Shop\Controller\Block\Cart"}
        {/if}
        {if $THEME_SETTINGS.enable_compare}
            {moduleinsert name="\Catalog\Controller\Block\Compare" indexTemplate="blocks/compare/cart_compare.tpl"}
        {/if}
        {if $THEME_SETTINGS.enable_favorite}
            {moduleinsert name="\Catalog\Controller\Block\Favorite"}
        {/if}
    </div>
</div>    