<p>{t}Уважаемый, администратор!{/t}</p>
<p>{t site = $url->getDomainStr()}На сайте %site зарегистрирован пользователь!{/t}</p>

<p>ID: {$data->user.id}<br>
{t}Ф.И.О.{/t}: {$data->user.name} {$data->user.surname} {$data->user.midname}<br>
E-mail: {$data->user.e_mail}<br>
{t}Телефон{/t}: {$data->user.phone}<br>
{if $data->user.is_company}{t}Название организации{/t}: {$data->user.company}<br>
{t}ИНН{/t}: {$data->user.company_inn}<br>
{/if}
-------------------------------------<br>
{t}Логин{/t}: {$data->user.login}<br>
{t}Пароль{/t}: {$data->password}<br>

<p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>