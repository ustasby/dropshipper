{addcss file="{$mod_css}tree.css" basepath="root"}
{addcss file="{$mod_css}groupedit.css" basepath="root"}

{if $elem.alias == 'supervisor'}
    <div style="margin-top:10px;" class="notice-box no-padd">
        <div class="notice-bg">
            {t}Супервизор имеет полные права ко всем модулям, сайтам и пунктам меню{/t}
        </div>
    </div>
{else}

<div class="switch-site-access">
    <input id="site_admin" type="checkbox" name="site_access" value="1" {if !empty($site_access)}checked{/if}>&nbsp;<label for="site_admin">{t}Включить доступ к администрированию текущего сайта{/t}</label>
</div>

<h3>{t}Доступ к пунктам меню{/t}</h3>
<br>

<div class="treeblock">
    <div class="left">
        <div class="full-access l-p-space">
            <input type="checkbox" name="menu_access[]" value="{$smarty.const.FULL_USER_ACCESS}" {if isset($menu_access[$smarty.const.FULL_USER_ACCESS])}checked{/if} id="full_user">
            <label for="full_user">{t}Полный доступ к меню пользователя{/t}</label>
        </div>
        <div class="lefttree localform" style="position:relative">
            <div class="overlay" id="user_overlay">&nbsp;</div>
            <div class="wrap">
                {$user_tree->getView()}
            </div>
        </div>
    </div>

    <div class="right">
        <div class="full-access l-p-space">
            <input type="checkbox" name="menu_admin_access[]" value="{$smarty.const.FULL_ADMIN_ACCESS}" {if isset($menu_access[$smarty.const.FULL_ADMIN_ACCESS])}checked{/if} id="full_admin">
            <label for="full_admin">{t}Полный доступ к меню администратора{/t}</label>
        </div>

        <div class="righttree localform" style="position:relative">
            <div class="overlay" id="admin_overlay">&nbsp;</div>
            <div class="wrap">
                {$admin_tree->getView()}
            </div>
        </div>
    </div>
</div> <!--Treeblock -->

<h3>{t}Права к модулям{/t}</h3>
<br>

<div class="full-access">
    <input type="checkbox" name="module_access[{$smarty.const.FULL_MODULE_ACCESS}]" value="255" {if isset($module_access[$smarty.const.FULL_MODULE_ACCESS])}checked{/if} id="full_module">
    <label for="full_module">{t}Полный доступ ко всем модулям{/t}</label>
</div>

<div class="moduleWrapper">
    <div class="overlay" id="module_overlay">&nbsp;</div>
    {$elements.table->getView()}
</div>

{literal}
<script>
putOverlay = function(options)
{
    var _this = this;
    this.options = options;
    this.overdiv = $(this.options.overlay);
    this.checkbox = $(this.options.checkbox);
    
    this.change = function()
    {
        var checked = (_this.options.checkshow) ? this.checked : !this.checked;
        if (checked) _this.showOverlay();
        else {
            _this.overdiv.hide();
        }
    }
    
    this.showOverlay = function()
    {
        var parentHeight = this.overdiv.parent().height();
        if (parentHeight>0) this.overdiv.height(parentHeight);
        this.overdiv.show();
    }
    
    this.defaultDraw = function()
    {
        //Включаем оверлей по умолчанию, если нужно
        var checked = (this.options.checkshow) ? this.checkbox.get(0).checked : !this.checkbox.get(0).checked;
        if (checked) this.showOverlay();
    }
    
    this.defaultDraw();
    this.checkbox.change(this.change);
}

var userfull;
var adminfull;
var modulefull;

$(function() {
    userfull = new putOverlay({checkbox: '#full_user', overlay:'#user_overlay', checkshow:true});
    adminfull = new putOverlay({checkbox: '#full_admin', overlay:'#admin_overlay', checkshow:true});
    modulefull = new putOverlay({checkbox: '#full_module', overlay:'#module_overlay', checkshow:true});    
});
</script>
{/literal}
{/if}