<p>{t domain = $url->getDomainStr()}Уважаемый, пользователь! Из службы поддержки поступило сообщение (отправленное с сайта %domain).{/t}</p>
{assign var=topic value=$data->support->getTopic()}
{assign var=user value=$data->user}
<p>{t}Дата{/t}: {$data->support.dateof}<br>
{t}Тема переписки{/t}: <strong>{$topic.title}</strong></p>


<h3>{t}Сообщение{/t}</h3>
{$data->support.message}

<p><a href="{$router->getUrl('support-front-support', ['Act' => 'ViewTopic', 'id' => $topic.id], true)}">{t}Ответить{/t}</a></p>

<p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>