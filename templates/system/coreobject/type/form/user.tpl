{addjs file="jquery.rs.userselect.js" basepath="common"}

<span class="form-inline">
    <div class="input-group">
        <input type="text" data-name="{$field->getFormName()}" class="user-select" {if $field->get()>0} value="{$field->getUser()->getFio()}"{/if} {$field->getAttr()} data-request-url="{$field->getRequestUrl()}">
        {if $field->get()>0}<input type="hidden" name="{$field->getFormName()}" value="{$field->get()}">{/if}
        <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
    </div>
</span>