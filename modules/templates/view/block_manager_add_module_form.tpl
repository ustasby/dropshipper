<div class="titlebox">{t}Выберите блок, который желаете вставить{/t}</div>
    <div class="module-blocks">
        <div class="left">
            <div class="columntitle">{t}Модули{/t}</div>
            <div class="dropdown">
                {$first_module=reset($controllers_tree)}
                <span id="current-module" class="dropdown-toggle gray-around" data-toggle="dropdown">
                    <span class="name">{$first_module.moduleTitle}</span> <i class="caret"></i>
                </span>
                <ul class="dropdown-menu modules"  aria-labelledby="current-module">
                    {foreach $controllers_tree as $mod_name => $module}
                    <li {if $module@first}class="act"{/if}><a data-view="mod-{$mod_name}">{$module.moduleTitle}</a></li>
                    {/foreach}
                </ul>
            </div>
        </div>
        <div class="right">
            <div class="columntitle">{t}Блоки{/t}</div>
            <div class="blocks">
                {foreach from=$controllers_tree item=module key=mod_name name="blist"}
                    <div id="mod-{$mod_name}" class="block-list{if !$smarty.foreach.blist.first} hidden{/if}">
                    {if !empty($module.controllers)}
                        {foreach from=$module.controllers item=block}
                            <a class="item crud-add" href="{adminUrl do=addModuleStep2 block=$block.class}" data-crud-options='{ "onLoadTrigger":"addModule", "beforeCallback": "addSectionId" }'>
                                <div class="limiter">
                                    <span class="name">{$block.info.title|default:$block.short_class}</span>
                                    <span class="info">{$block.info.description}</span>
                                </div>
                            </a>                            
                        {/foreach}
                    {/if}
                </div>
                {/foreach}                
            </div>
        </div>
        <div class="clear"></div>
    
        <script>
            $(function() {
                $('.module-blocks .modules a[data-view]').click(function() {
                    $('.act', $(this).closest('.modules')).removeClass('act');
                    $(this).closest('li').addClass('act');
                    
                    $('.module-blocks .blocks .block-list').addClass('hidden');
                    $('#'+$(this).data('view')).removeClass('hidden');

                    $('#current-module .name').text($(this).text());
                });
                
                $(window).bind('addModule', function(e, response) {
                    $('#blockListDialog').dialog('close');
                    if (response.close_dialog) {
                        $($.rs.updatable.dom.defaultContainer).trigger('rs-update');
                    }
                });
            });
            
            function addSectionId(options) {
                var dialogOptions = $('#blockListDialog').dialog('option', 'crudOptions');
                options.extraParams = { section_id: dialogOptions.sectionId };
                return options;
            }
        </script>
    </div>
</div>