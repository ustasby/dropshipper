{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
{$shop_config=ConfigLoader::byModule('shop')}
{$route_id=$router->getCurrentRoute()->getId()}
    <div class="box profile">
        {* Хлебные крошки *}
        {moduleinsert name="\Main\Controller\Block\BreadCrumbs"}
        {if $route_id != 'shop-front-myorderview'}<h1 class="textCenter">Личный кабинет</h1>{/if}
        <div class="rel">
            <div class="rightColumn productList">
                {$app->blocks->getMainContent()}
            </div>
            <div class="leftColumn">
                <ul class="profileMenu">
                    <li {if $route_id=='users-front-profile'}class="act"{/if}><a href="{$router->getUrl('users-front-profile')}">Профиль</a></li>
                    <li {if in_array($route_id, ['shop-front-myorders', 'shop-front-myorderview'])}class="act"{/if}><a href="{$router->getUrl('shop-front-myorders')}">Мои заказы</a></li>
                    
                    {if $shop_config.use_personal_account}
                    <li {if $route_id=='shop-front-mybalance'}class="act"{/if}><a href="{$router->getUrl('shop-front-mybalance')}">Лицевой счет</a></li>
                    {/if}
                    
                    {if ModuleManager::staticModuleExists('support')}
                    <li {if $route_id=='support-front-support'}class="act"{/if}><a href="{$router->getUrl('support-front-support')}">Сообщения</a></li>
                    {/if}
                    
                    {if ModuleManager::staticModuleExists('partnership')}
                    {static_call var="is_partner" callback=['Partnership\Model\Api', 'isUserPartner'] params=$current_user.id}
                        {if $is_partner}
                            <li {if $route_id=='partnership-front-profile'}class="act"{/if}><a href="{$router->getUrl('partnership-front-profile')}">Профиль партнера</a></li>
                        {/if}
                    {/if}
                    
                    <li><a href="{$router->getUrl('users-front-auth', ['Act' => 'logout'])}">Выход</a></li>
                </ul>
            </div>
        </div>
        <div class="clearBoth"></div>
    </div>
{/block}