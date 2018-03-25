{* Это единый шаблон просмотра узла дерева структуры данных. Вы можете его использовать для отображения разных типов страниц.
Определять тип страницы лучше по полю `alias`. Для каждого типа страницы рекомендуем подключать собственный под-шаблон.
Пример:

{if $node->getParent()->alias == 'services'}
    {include file="service_view.tpl"}
{elseif $node->getParent()->alias == 'pages'}
    {include file="page_view.tpl"}
{/if}

Если на одной странице нужно отобразить различные элементы узлов дерева, то нужно воспользоваться блочным контролером:
{moduleinsert name="DataBlocks\Controller\Block\NodeView" node_alias="youralias"}
*}

{* Текущий элемент *}
<p>{t}Элемент:{/t} {$node.title}</p>

{* Для обращения к статическим полям, можно использовать: $node.title, $node.alias, $node.public, $node.sortn *}
{* Для обращения к значениям динамических полей: $node.fld_custom_name1, $node.fld_custom_name2 *}
{* Для обращения к свойствам динамических полей: $node->getDynamicProperty('custom_name1') *}
{* Для обращения к фотографиям галереи $node->getPhotogalleryImages('custom_name') *}
{* Для получения ссылки на узел: $node->getUrl() *}

{* Поучение всех дочерних элементов *}
{$childs = $node->getChilds()}
{if $childs}
    <p>{t}Дочерние элементы:{/t}</p>
    <ul>
        {foreach $childs as $child}
            <li>{$child.title}</li>
            {foreachelse}
            <li>{t}Нет элементов{/t}</li>
        {/foreach}
    </ul>
{/if}

{* Получение одной страницы дочерних элементов
{$page = 1}
{$page_size = 20}
{$childs_page = $node->getChilds($page, $page_size)}
*}

{* Получение родительского элемента *}
{$parent = $node->getParent()}
{if $parent->id}
    <p>{t}Родитель:{/t} {$parent.title}</p>
{/if}

{* Получение родителя родительского элемента *}
{$parent_parent = $node->getParent()->getParent()}
{if $parent_parent->id}
    <p>{t}Родитель родителя:{/t} {$parent_parent.title}</p>
{/if}