<div class="m-b-10">
    <a href="{adminUrl pid=0}">
        <i class="zmdi zmdi-folder-outline icon f-18" data-toggle-class="left-open" data-target-closest=".crud-view-table-tree"></i>
    </a>
    {foreach $current_path as $item}
        <i class="zmdi zmdi-chevron-right m-l-5 m-r-5"></i>
        <a class="item" href="{adminUrl pid=$item.id}">{$item.title}</a>
    {/foreach}
</div>