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

// Подключаем библиотеку modeladmin Joomla.
jimport('joomla.application.component.modeladmin');

// Подключаем библиотеку helper Joomla.
jimport('joomla.application.component.helper');

/**
 * Модель Category_MenuItem.
 * @since 0.4.4
 */
class YandexMarketModelCategory_MenuItem extends JModelAdmin {
    /**
     * YandexMarketModelCategory_MenuItem constructor.
     * @param array $config
     * @since 0.4.4
     */
    function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Возвращает ссылку на объект таблицы, всегда его создавая.
     *
     * @param   string  $type    Тип таблицы для подключения.
     * @param   string  $prefix  Префикс класса таблицы. Необязателен.
     * @param   array   $config  Конфигурационный массив. Необязателен.
     *
     * @return  JTable  Объект JTable.
     * @since 0.4.4
     */
    public function getTable($type = 'category', $prefix = 'YandexMarketTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Метод для получения формы.
     *
     * @param   array    $data      Данные для формы.
     * @param   boolean  $loadData  True, если форма загружает свои данные (по умолчанию), false если нет.
     * @return  mixed  Объект JForm в случае успеха, в противном случае false.
     * @since 0.4.4
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Получаем форму.
        $form = $this->loadForm(
            $this->option . '.category_menuitem', 'category_menuitem', array('control' => 'jform', 'load_data' => $loadData)
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Метод для получения данных, которые должны быть загружены в форму.
     *
     * @return  mixed  Данные для формы.
     * @since 0.4.4
     */
    protected function loadFormData()
    {
        // Проверка сессии на наличие ранее введеных в форму данных.
        $data = JFactory::getApplication()->getUserState($this->option . '.edit.category_menuitem.data', array());

        //если в сессии данных нет, то нужно достать их из базы
        if (empty($data))  {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Метод для получения одной записи.
     *
     * @param   integer $pk The id of the primary key.
     * @return  mixed  Object on success, false on failure.
     * @since 0.4.4
     */
    public function getItem($pk = null)
    {

        $item = parent::getItem($pk);

        // Если поле id не заполнено, значит из базы ничего не достали, а следовательно это новая запись,
        // в которой надо заполнить как минимум поля category_id и category_parent_id
        if (empty($item->category_id)) {
            //достаём и подставляем в $item->parent_id родительскую категорию
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->clear()
                ->select('category_parent_id')
                ->from('#__hikashop_category')
                ->where('category_id = ' . $db->quote($this->state->get('category_menuitem.id')));
            $item->category_parent_id = (int)$db->setQuery($query)->loadResult();

            //подставляем в $item->id идентификатор категории
            $item->category_id = $this->state->get('category_menuitem.id');
        }

        return $item;
    }
} //YandexMarketModelCategory_MyName
