<a class="debug-icon debug-icon-info debug-hint" onclick="window.open('{adminUrl mod_controller="main-debug" do="showVars" toolgroup="{$tool->getUniq()}"}','popup{$tool->getUniq()}', 'width=400,height=300,scrollbars=yes')" title="
<strong>Информация о блоке</strong><br>
Название: {$tool->getConfig('name')}<br>
Описание: {$tool->getConfig('description')}<br>
Класс: {$tool->getModule()->getName()}<br>
Версия: {$tool->getConfig('version')}<br>
Автор: {$tool->getConfig('author')}<br>
Контроллер: {$tool->getControllerName()}<br>
Шаблон: {$tool->render_template}"></a>