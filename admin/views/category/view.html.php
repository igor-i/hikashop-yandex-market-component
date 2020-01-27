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
 * HTML представление редактирования категории и настроек товарных предложений.
 * @since 0.1
 */
class YandexMarketViewCategory extends MyLibBase {
    /**
     * Сообщение.
     * @var  object
     * @since 0.1
     */
    protected $item;

    /**
     * Объект формы.
     * @var  array
     * @since 0.1
     */
    protected $form;

    /**
     * Стандартный футер для всех админских страниц
     * @var
     * @since 0.1
     */
    protected $footer;

    /**
     * Отображает представление.
     *
     * @param   string  $tpl  Имя файла шаблона.
     * @return  void
     * @throws  Exception
     * @since 0.1
     */
    public function display($tpl = null) {
        // Получаем данные из модели.
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        // Устанавливаем панель инструментов (для модального окна не надо).
        if ($this->getLayout() !== 'modal') {
            $this->addToolBar();
        }

        // Устанавливаем подвал (копирайт и прочее)
        $this->footer = MylibBase::displayFooter();

        $model = $this->getModel();
        if (count($errors = $model->getErrors())) {
            throw new Exception(implode("\n", $errors));
        }

        // Отображаем представление.
        parent::display($tpl);
    }

    /**
     * Устанавливает панель инструментов.
     *
     * @return  void
     * @since 0.1
     */
    protected function addToolBar() {
        JFactory::getApplication()->input->set('hidemainmenu', true);
        $isNew = ($this->item->id == 0);

        $title = 'COM_YANDEXMARKET_PAGE_VIEW_OFFERS_' . ($isNew ? 'ADD' : 'EDIT');
        MylibBase::setTitle($title);

        JToolBarHelper::apply('category.apply');
        JToolBarHelper::save('category.save');

        $alt = $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE';
        JToolBarHelper::cancel('category.cancel', $alt);
    }
} //YandexMarketViewCategory
