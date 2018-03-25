<div class="bodyWrap checkout">
    <header>
        <div class="viewport">
            {* Логотип *}
            {moduleinsert name="\Main\Controller\Block\Logo" width="200" height="75"}
            
            {* Корзина *}
            {moduleinsert name="\Shop\Controller\Block\Cart" indexTemplate="blocks/cart/co_cart.tpl"}
            
            {* Шаги оформления заказа *}
            {moduleinsert name="\Shop\Controller\Block\CheckoutStep"}               
        </div>
    </header>
    <div class="viewport mainContent">
        {* Главное содержимое страницы *}
        {$app->blocks->getMainContent()}
        <footer>
            <div class="copyline">
                <a href="" class="developer">Работает на <span>ReadyScript</span></a>            
                <span class="copy">&copy; {"now"|dateformat:"Y"} Все права защищены</span>
            </div>
        </footer>
    </div>
</div>