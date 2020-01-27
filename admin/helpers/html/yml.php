<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */

defined('_JEXEC') or die();

JTable::addIncludePath(JPATH_COMPONENT . '/tables');

/**
 * @package     Yandex.Market for HikaShop
 * @subpackage     com_yandexmarket
 * @since 0.1
 */
abstract class JHtmlYml {

    /**
     * @param $name
     * @param string $value
     * @param int $j
     * @return mixed
     * @since 0.1
     */
    public static function changefrequency($name, $value = 'weekly', $j = 0) {
        // Array of options
        $options[] = JHTML::_('select.option', 'hourly', JText::_('COM_YANDEXMARKET_HOURLY'));
        $options[] = JHTML::_('select.option', 'daily', JText::_('COM_YANDEXMARKET_DAILY'));
        $options[] = JHTML::_('select.option', 'weekly', JText::_('COM_YANDEXMARKET_WEEKLY'));
        $options[] = JHTML::_('select.option', 'monthly', JText::_('COM_YANDEXMARKET_MONTHLY'));
        $options[] = JHTML::_('select.option', 'yearly', JText::_('COM_YANDEXMARKET_YEARLY'));
        $options[] = JHTML::_('select.option', 'never', JText::_('COM_YANDEXMARKET_NEVER'));

        return JHtml::_('select.genericlist', $options, $name, null, 'value', 'text', $value, $name . $j);
    }
} //JHtmlYandexMarket
