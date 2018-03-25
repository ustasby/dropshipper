{if $items}
<nav class="topMenu">
    <a href="#" class="mobileHandler rs-parent-switcher">{t}Меню{/t} <i></i></a>
    <ul>
        {include file="blocks/menu/branch.tpl" menu_level=$items}
    </ul>
</nav>
{else}
    {include file="%THEME%/block_stub.tpl"  class="noBack blockSmall blockLeft blockMenu" do=[
        {adminUrl do="add" mod_controller="menu-ctrl"} => t("Добавьте пункт меню")
    ]}
{/if}