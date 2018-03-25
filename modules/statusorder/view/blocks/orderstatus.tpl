{addcss file="{$mod_css}orderstatus.css" basepath="root"}
{addjs file="{$mod_js}orderstatus.js" basepath="root"}
<div class="order-status__overlay"></div>
<div class="order-status__window">
	<form name="order-status" method="post" action="{$router->getUrl('statusorder-front-ctrl', ['_block_id'=>$this_controller->getBlockId()])}">
	<ul>
	<li><span class="orderstatus-icon"></span></li>
	<li>Статус моего заказа</li>
	{if $authFrom==1}
	<li class="input"><input type="text" name="email" value="" placeholder="Введите адрес электронной почты" /></li>
	{elseif $authFrom==2}
	<li class="input"><input type="text" name="phone" value="" placeholder="Введите номер телефона" /></li>
	{/if}
	<li><input type="text" name="order-number" value="" placeholder="Введите номер Вашего заказа" /></li>
	<li><button name="getOrderStatus">Посмотреть</button></li>
	<li class="loading"><img src="{$mod_css}../img/loading.svg" /></li>
	</ul>
	</form>
	<a href="javascript:;" class="order-status__close">&#10006;</a>
</div>
<div class="order-status__result-window">
	<a href="javascript:;" class="order-status__back">&laquo; Вернуться</a>
	<div class="order-status__result">
	</div>
	<a href="javascript:;" class="order-status__close">&#10006;</a>
</div>
<div class="order-status">
	<a href="javascript:;" class="order-status__verify">{$buttonText}</a>
</div>