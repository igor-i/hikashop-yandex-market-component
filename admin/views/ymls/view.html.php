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

// Подключаем библиотеку представления Joomla.
jimport('joomla.application.component.view');

/**
 * HTML представление списка элементов (YML's) компонента Yandex.Market.
 * @since 0.1
 */
class YandexMarketViewYmls extends MyLibBase
{
    /**
     * Элементы (здесь YML's).
     * @var  array
     * @since 0.1
     */
    protected $items;

    /**
     * Постраничная навигация.
     * @var  object
     * @since 0.1
     */
    protected $pagination;

    /**
     * Состояние модели.
     * @var \Joomla\Registry\Registry
     * @since 0.1
     */
    protected $state;
    protected $languages;
    protected $item;
    public $filterForm;
    public $activeFilters;

    /**
     * Стандартный футер для всех админских страниц
     * @var
     * @since 0.1
     */
    protected $footer;

    /**
     * Отображаем список сообщений.
     *
     * @param   string  $tpl  Имя файла шаблона.
     * @return  void
     * @throws  Exception
     * @since 0.1
     */
    public function display($tpl = null)
    {
        try {
            // Получаем данные из модели.
            $this->items         = $this->get('Items');
            // Получаем объект постраничной навигации.
            $this->pagination    = $this->get('Pagination');
            // Получаем объект состояния модели.
            $this->state         = $this->get('State');
            // Фильтр
            $this->filterForm    = $this->get('FilterForm');
            $this->activeFilters = $this->get('ActiveFilters');

            // Устанавливаем панель инструментов (для модального окна не надо).
            if ($this->getLayout() !== 'modal') {
                $this->addToolBar();
            }
            // Устанавливаем подвал (копирайт и прочее)
            $this->footer = MylibBase::displayFooter();

            // Get the active languages for multi-language sites
            $this->languages = null;
            if (JLanguageMultilang::isEnabled()) {
                $this->languages = JLanguageHelper::getLanguages();
            }

            // Отображаем представление.
            parent::display($tpl);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Устанавливает панель инструментов.
     * @param bool $addDivider
     * @since 0.1
     */
    protected function addToolBar($addDivider = true)
    {
        MylibBase::setTitle('COM_YANDEXMARKET_SUBMENU_YMLS');
        MylibBase::addSubmenu('ymls');

        JToolBarHelper::addNew('yml.add');
        JToolBarHelper::editList('yml.edit');
        JToolBarHelper::custom('ymls.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_Publish', true);
        JToolBarHelper::custom('ymls.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
        JToolBarHelper::custom(
            'yml.setAsDefault',
            'featured.png',
            'featured_f2.png',
            'JTOOLBAR_DEFAULT',
            true
        );

        if ($addDivider) {
            JToolBarHelper::divider();
        }

        if ($this->state->get('filter.published') == -2) {
            JToolBarHelper::deleteList('', 'ymls.delete', 'JTOOLBAR_DELETE');
        } else {
            JToolBarHelper::trash('ymls.trash', 'JTOOLBAR_TRASH');
        }

        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_yandexmarket')) {
            if ($addDivider) {
                JToolBarHelper::divider();
            }
            JToolBarHelper::preferences('com_yandexmarket');
        }
    }
} //YandexMarketViewYandexYmls
