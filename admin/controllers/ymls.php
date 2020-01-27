<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

class YandexMarketControllerYmls extends JControllerAdmin
{
    public function getModel($name = 'Yml', $prefix = 'YandexMarketModel')
    {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }
} //YandexMarketControllerYmls
