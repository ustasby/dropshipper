{$transaction=$cell->getRow()}
{if $transaction.user_id>0}
    {$user=$transaction->getUser()}
    {$user->getFio()} <span class="cell-sgray">({$cell->getValue()})</span>
    {if $user.is_company}<div class="cell-sgray">{$user.company}, {t}ИНН{/t}: {$user.company_inn}</div>{/if}
{else}
    {if !empty($transaction.user_fio)}{$transaction.user_fio}{else}{t}Пользователь не указан{/t}{/if}  <span class="cell-sgray">({t}Без регистрации{/t})</span>
{/if}