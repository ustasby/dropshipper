{* Шаблон страницы пополнения баланса в личном кабинете *}

<div class="form-style">
    <ul class="nav nav-tabs hidden-xs hidden-sm">
        <li><a href="{$router->getUrl('shop-front-mybalance')}">{t}История операций{/t}</a></li>
        <li class="active"><a>{t}Пополнить баланс{/t}</a></li>
    </ul>
    <div class="tab-content">
        <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs">
            <span>{t}Пополнить баланс{/t}</span>
        </div>
        <div>
            <h2 class="h2 t-balance_title">{t}Ваш баланс{/t}: {$current_user->getBalance(true, true)}</h2>
            <p>{t}Вы можете пополнить свой баланс наиболее удобным способом {/t}</p>
        </div>

    </div>

    <form method="POST">
        <div class="tab-content">
            <div class="tab-pane active">
                <h2 class="h2 t-balance_title">{t}Сумма пополнения{/t}</h2>

                <div class="form-group">
                    <label class="label-sup">{t}Укажите сумму{/t}</label>
                    <input type="text" name="cost" value="{$smarty.post.cost}" placeholder="{t}0.00{/t}" {if $api->hasError()}class="has-error"{/if} required>

                    {if $api->hasError()}
                        <div class="page-error">
                            {foreach $api->getErrors() as $item}
                                <p>{$item}</p>
                            {/foreach}
                        </div>
                    {/if}
                </div>
                <p>{t}Укажите сумму и выберите ниже подходящий способ оплаты{/t}</p>
            </div>
        </div>


        <div class="t-pay_wrapper">
            {foreach $pay_list as $item}
                <button name="payment" type="submit" value="{$item.id}" class="t-pay-receipt">
                    <div class="inside">
                        <h2 class="h2">{$item.title}</h2>
                        <p>{$item.description}</p>
                    </div>
                </button>
            {/foreach}
        </div>
    </form>


</div>