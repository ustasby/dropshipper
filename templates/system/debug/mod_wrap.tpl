<div class="module-wrapper" {$group->getDebugAttributes()}>
    <div class="module-border module-border-left"></div>
    <div class="module-border module-border-right"></div>
    <div class="module-border module-border-top"></div>
    <div class="module-border module-border-bottom"></div>
    
    <div class="module-tools">    
        <div class="dragblock">&nbsp;</div>
        {foreach from=$group->getTools() item=tool}
            {$tool->getView()}
        {/foreach}
    </div>
    <div class="module-content">
        {$result_html}
    </div>
</div>