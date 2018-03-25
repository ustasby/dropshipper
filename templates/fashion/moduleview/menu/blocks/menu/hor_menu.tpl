{if $items}
    <ul class="menu">
        {include file="blocks/menu/branch.tpl" menu_level=$items}
    </ul>
{else}
    {include file="%THEME%/block_stub.tpl"  class="noBack blockSmall blockLeft blockMenu" do=[
        {adminUrl do="add" mod_controller="menu-ctrl"} => t("Добавьте пункт меню")
    ]}
{/if}