<?php
namespace SaleDocs\Model;
require_once(__DIR__.'/dompdf/dompdf_config.inc.php');

class PdfGenerator
{
    function outputPdf($template, $vars)
    {
        $view = new \RS\View\Engine();
        $html = $view->assign($vars)->fetch($template);                                              
        
        $config = \RS\Config\Loader::byModule($this);
        $pdf_cache_file = $config->getModuleStorageDir().'/pdf-'.md5($html).'.pdf';
        
        \RS\Application\Application::getInstance()->headers->addHeader('content-type', 'application/pdf');
        if ($config['cache_pdf'] && file_exists($pdf_cache_file)) {
            return file_get_contents($pdf_cache_file);
        } else {
            $dompdf = new \DOMPDF();
            $dompdf->load_html($html);
            $dompdf->render();
            $pdf_content = $dompdf->output();
            
            if ($config['cache_pdf']) {
                file_put_contents($pdf_cache_file, $pdf_content);
            }
            
            return $pdf_content;
        }
    }
}

?>
