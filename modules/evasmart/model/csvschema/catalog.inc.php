<?php
namespace Evasmart\Model\CsvSchema;

use Evasmart\Model\CsvPreset\Product;
use RS\Csv\AbstractSchema;


class Catalog extends AbstractSchema
{


    function __construct($api)
    {

        parent::__construct(
            new Product(array(
                'api' => $api
            ))
        );
    }

}
