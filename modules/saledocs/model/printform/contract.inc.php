<?php
namespace SaleDocs\Model\PrintForm;
use \Shop\Model\PrintForm\AbstractPrintForm;

/**
* Обычная печатная форма заказа
*/
class Contract extends AbstractPrintForm
{
    /**
    * Возвращает краткий символьный идентификатор печатной формы
    * 
    * @return string
    */
    function getId()
    {
        return 'sd-contract';
    }
    
    /**
    * Возвращает название печатной формы
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Договор');
    }
    
    /**
    * Возвращает шаблон формы
    * 
    * @return string
    */
    function getTemplate()
    {
        return \RS\Config\Loader::byModule($this)->contract_tpl;
    }
    
    /**
    * Возвращает результат в формате PDF
    */
    function getHtml()
    {
        $pdf_generator = new \SaleDocs\Model\PdfGenerator();
        return $pdf_generator->outputPdf($this->getTemplate(), array(
            'order' => $this->order
        ));
    }
}
