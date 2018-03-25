{addjs file="jquery.min.js" name="jquery" basepath="common" header=true}
{addjs file="jquery.ui/jquery-ui.min.js" basepath="common"}
{addjs file="dialog-options/jquery.dialogoptions.js" basepath="common"}
{addjs file="bootstrap/bootstrap.min.js" name="bootstrap" basepath="common"}

{addjs file="lab/lab.min.js" basepath="common"}
{addjs file="jquery.rs.admindebug.js" basepath="common"}

{addjs file="jquery.datetimeaddon/jquery.datetimeaddon.min.js" basepath="common"}
{addjs file="jquery.rs.debug.js" basepath="common"}
{addjs file="jquery.rs.ormobject.js" basepath="common"}
{addjs file="jquery.cookie/jquery.cookie.js" basepath="common"}
{addjs file="jquery.form/jquery.form.js" basepath="common"}
{addjs file="jstour/jquery.tour.engine.js" basepath="common"}
{addjs file="jstour/jquery.tour.js" basepath="common"}

{addcss file="flatadmin/iconic-font/css/material-design-iconic-font.min.css" basepath="common"}
{addcss file="flatadmin/readyscript.ui/jquery-ui.css" basepath="common"}
{addcss file="flatadmin/app.css" basepath="common"}
{addcss file="common/animate.css" basepath="common"}
{addcss file="common/tour.css" basepath="common"}

{if $this_controller->getDebugGroup()}
    {addcss file="flatadmin/debug.css" basepath="common"}
{/if}

<div id="debug-top-block" class="admin-style">
    <header id="header">
        <ul class="header-inner">
            <li class="rs-logo debug">
                <a href="{$router->getRootUrl()}"></a>
            </li>

            <li class="header-panel">
                <div class="viewport">
                    <div class="fixed-tools">
                        <a href="{$router->getUrl('main.admin')}" class="to-admin">
                            <i class="rs-icon rs-icon-admin"></i><br>
                            <span>{t}управление{/t}</span>
                        </a>

                        <a href="{$router->getUrl('main.admin', ["Act" => "cleanCache"])}" class="rs-clean-cache">
                            <i class="rs-icon rs-icon-refresh"></i><br>
                            <span>{t}кэш{/t}</span>
                        </a>

                        <div class="debug-mode-switcher">
                            <div data-url="{$router->getUrl('main.admin', [Act => 'ajaxToggleDebug'])}" class="toggle-switch rs-switch {if $this_controller->getDebugGroup()}on{/if}">
                                <label class="ts-helper"></label>
                            </div>
                            <p class="debugmode-text"><span class="hidden-xs">{t}режим отладки{/t}</span><span class="visible-xs">{t}отладка{/t}</span></p>
                        </div>
                    </div>

                    <div class="float-tools">
                        <div class="dropdown">
                            <a class="toggle visible-xs-inline-block" data-toggle="dropdown" id="floatTools" aria-haspopup="true"><i class="zmdi zmdi-more-vert"></i></a>

                            <ul class="ft-dropdown-menu" aria-labelledby="floatTools">
                                {moduleinsert name="\Main\Controller\Admin\Block\HeaderPanel" public=true indexTemplate="%main%/adminblocks/headerpanel/header_public_panel_items.tpl"}
                                <li>
                                    <a class="hidden-xs action start-tour" data-tour-id="welcome" title="{t}Обучение{/t}">
                                        <i class="rs-icon rs-icon-tour"></i>
                                        <span>{t}Обучение{/t}</span>
                                    </a>
                                </li>
                                <li class="ft-hover-node">
                                    <a href="{adminUrl mod_controller="users-ctrl" do="edit" id=$current_user.id}">
                                        <i class="rs-icon rs-icon-user"></i>
                                        <span>{$current_user->getFio()}</span>
                                    </a>

                                    <ul class="ft-sub">
                                        <li>
                                            <a href="{$router->getUrl('main.admin', [Act => 'logout'])}">
                                                <i class="rs-icon zmdi zmdi-power"></i>
                                                <span>{t}Выход{/t}</span>
                                            </a>
                                        </li>
                                    </ul>

                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </header>
</div>
{$result_html}