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
 * HTML представление редактирования элемента (YML).
 * @since 0.1
 */
class YandexMarketViewYml extends MyLibBase {
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
        $app = JFactory::getApplication();
        $model = $this->getModel();

        // Получаем данные из модели.
        $this->form = $model->getForm();
        $this->item = $model->getItem();

        // Convert dates from UTC
        $offset = $app->get('offset');
        if (intval($this->item->created_on)) {
            $this->item->created_on = JHtml::date($this->item->created_on, '%Y-%m-%d %H-%M-%S', $offset);
        }

        // Устанавливаем панель инструментов (для модального окна не надо).
        if ($this->getLayout() !== 'modal') {
            $this->addToolBar();
        }

        // Устанавливаем подвал (копирайт и прочее)
        $this->footer = MylibBase::displayFooter();

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

        $title = 'COM_YANDEXMARKET_PAGE_VIEW_YML_' . ($isNew ? 'ADD' : 'EDIT');
        MylibBase::setTitle($title);
//        JToolBarHelper::title(JText::_('COM_YANDEXMARKET'), 'yandexmarket');

        JToolBarHelper::apply('yml.apply');
        JToolBarHelper::save('yml.save');
        JToolBarHelper::save2new('yml.save2new');

        if (!$isNew) {
            JToolBarHelper::save2copy('yml.save2copy');
        }

        $alt = $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE';
        JToolBarHelper::cancel('yml.cancel', $alt);
    }
} //YandexMarketViewYml
