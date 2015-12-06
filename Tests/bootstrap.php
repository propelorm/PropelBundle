<?php

require_once __DIR__ . '/../vendor/autoload.php';

if(!class_exists('Symfony\\Component\\Form\\Test\\TypeTestCase')) {
    class_alias('Symfony\\Component\\Form\\Tests\\Extension\\Core\\Type\\TypeTestCase', 'Symfony\\Component\\Form\\Test\\TypeTestCase');
}
