{t alias="Подсказка по SEO генератору"}В этом поле Вы можете использовать переменные, вместо которых будут<br/> 
подставлены соотвествующие значения:  <br/><br/>
{foreach from=$hints item=value key=key}
    &nbsp;&nbsp;<b>{ldelim}{$key}{rdelim}</b> - {$value} <br/>
{/foreach}<br/>
<b>Характеристики:</b><br/>
&nbsp;&nbsp;<b>{ldelim}prop.id{rdelim}</b> - Значение характеристики, где id - это номер характеристики<br/> 
<br/>
Сокращайте заменяемый текст с помощью конструкции {ldelim}title|100{rdelim},<br/>
где - 100 это количество символов от начала в значении,<br/>
a title - это доступное поле
<br/><br/>
<b>{ldelim}title|.|3{rdelim}</b> - эта конструкция обрежет заголовок(поле title)<br/> 
предложение до третьей точки (".") включительно.<br/>
Первый аргумент после "|" это поле объекта, второй символ поиска, третий число вхождений.{/t}