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

if (!empty($this->item->menuitem)) {
    echo JHtml::_(
            'link',
            '#',
            '<small>' . $this->escape($this->item->menuitem) . ': ' . $this->escape($this->item->menuitem_title) . '</small> <span class="icon-edit"></span>',
            array('onclick' => 'loadCatMenuItem(this); return false;', 'data-id' => $this->item->id)
        );
} else {
    echo JHtml::_(
        'link',
        '#',
        '<span class="icon-edit"></span>',
        array('onclick' => 'loadCatMenuItem(this); return false;', 'data-id' => $this->item->id)
    );
}
