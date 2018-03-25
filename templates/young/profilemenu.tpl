{$shop_config=ConfigLoader::byModule('shop')}
{$route_id=$router->getCurrentRoute()->getId()}
<div class="personalMenu">
    <h3>{t}Личный кабинет{/t}</h3>
    <ul>
        {hook name="users-blocks-authblock:cabinet-menu-items" title="{t}Блок авторизации:пункты меню личного кабинета{/t}"}
            <li {if $route_id=='users-front-profile'}class="act"{/if}><a href="{$router->getUrl('users-front-profile')}">{t}Профиль{/t}</a></li>
            <li {if in_array($route_id, ['shop-front-myorders', 'shop-front-myorderview'])}class="act"{/if}><a href="{$router->getUrl('shop-front-myorders')}">{t}Мои заказы{/t}</a></li>
            {if $shop_config.return_enable}
                <li {if $route_id=='shop-front-myproductsreturn'}class="act"{/if}><a href="{$router->getUrl('shop-front-myproductsreturn')}">{t}Мои возвраты{/t}</a></li>
            {/if}
            {if $shop_config.use_personal_account}
                <li {if $route_id=='shop-front-mybalance'}class="act"{/if}><a href="{$router->getUrl('shop-front-mybalance')}">{t}Лицевой счет{/t}</a></li>
            {/if}
        {/hook}
        
        <li><a href="{$router->getUrl('users-front-auth', ['Act' => 'logout'])}">{t}Выход{/t}</a></li>
    </ul>
</div>