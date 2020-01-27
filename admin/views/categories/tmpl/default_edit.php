<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */
// Запрет прямого доступа.
defined('_JEXEC') or die;

echo JHtml::_(
    'link',
    '#',
    $this->escape($this->item->myname) . ' <span class="icon-edit"></span>',
    array('onclick' => 'loadCatMyName(this); return false;', 'data-id' => $this->item->id)
);
