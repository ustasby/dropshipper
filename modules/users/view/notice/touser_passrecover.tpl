{t alias = "Сообщение пользователю, восстановление пароля"
site = $url->getDomainStr()
login = $data->user.login
pass = $data->password
}

<p>Ваш пароль на сайте %site был изменен.</p>
<p>Теперь Вы можете войти в личный кабинет со следующими данными:</p>

<p>Логин: %login<br>
Пароль: %pass</p>

<p>С наилучшими пожеланиями, <br>
     администрация интернет магазина %site.</p>{/t}