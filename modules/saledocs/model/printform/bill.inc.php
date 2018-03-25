<?php
namespace SaleDocs\Model\PrintForm;
use \Shop\Model\PrintForm\AbstractPrintForm;

/**
* Обычная печатная форма заказа
*/
class Bill extends AbstractPrintForm
{
    /**
    * Возвращает краткий символьный идентификатор печатной формы
    * 
    * @return string
    */
    function getId()
    {
        return 'sd-bill';
    }
    
    /**
    * Возвращает название печатной формы
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Счет');
    }
    
    /**
    * Возвращает шаблон формы
    * 
    * @return string
    */
    function getTemplate()
    {
        return \RS\Config\Loader::byModule($this)->bill_tpl;
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