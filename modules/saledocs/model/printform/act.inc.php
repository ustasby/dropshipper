<?php
namespace SaleDocs\Model\PrintForm;
use \Shop\Model\PrintForm\AbstractPrintForm;

/**
* Обычная печатная форма заказа
*/
class Act extends AbstractPrintForm
{
    /**
    * Возвращает краткий символьный идентификатор печатной формы
    * 
    * @return string
    */
    function getId()
    {
        return 'sd-act';
    }
    
    /**
    * Возвращает название печатной формы
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Акт приема-передачи');
    }
    
    /**
    * Возвращает шаблон формы
    * 
    * @return string
    */
    function getTemplate()
    {
        return \RS\Config\Loader::byModule($this)->act_tpl;
    }
    
    /**
    * Возвращает результат в формате PDF
    */
    function getHtml()
    {
        $pdf_generator = new \SaleDocs\Model\PdfGenerator();
        $rs_tools = new \RS\Helper\Tools();
        return $pdf_generator->outputPdf($this->getTemplate(), array(
            'order' => $this->order,
            'rs_tools' => $rs_tools,
        ));
    }
}