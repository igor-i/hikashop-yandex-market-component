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
 * Модель Category.
 * @since 0.1
 */
class YandexMarketModelCategory extends JModelAdmin {
    /**
     * YandexMarketModelCategory constructor.
     * @param array $config
     * @since 0.1
     */
    function __construct($config = array()) {
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
     * @since 0.1
     */
    public function getTable($type = 'category', $prefix = 'YandexMarketTable', $config = array()) {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Метод для получения формы.
     *
     * @param   array    $data      Данные для формы.
     * @param   boolean  $loadData  True, если форма загружает свои данные (по умолчанию), false если нет.
     * @return  mixed  Объект JForm в случае успеха, в противном случае false.
     * @since 0.1
     */
    public function getForm($data = array(), $loadData = true) {
        // Получаем форму.
        $form = $this->loadForm(
            $this->option . '.category', 'category', array('control' => 'jform', 'load_data' => $loadData)
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
     * @since 0.1
     */
    protected function loadFormData() {
        // Проверка сессии на наличие ранее введеных в форму данных.
        $data = JFactory::getApplication()->getUserState($this->option . '.edit.category.data', array());

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
     * @since 0.1
     */
    public function getItem($pk = null) {
        $db = JFactory::getDbo();
        $item = parent::getItem($pk);

        //если поле id не заполнено, значит из базы ничего не достали, а следовательно это новая запись,
        //в которой надо заполнить как минимум поля category_id и category_parent_id
        if (empty($item->category_id)) {
            //достаём и подставляем в $item->parent_id родительскую категорию
            $query = $db->getQuery(true)
                ->select('category_id, category_parent_id, category_name')
                ->from('#__hikashop_category')
                ->where('category_id = ' . $db->quote($this->state->get('category.id')));
            $data = $db->setQuery($query)->loadAssoc();
            $item->category_id = (int)$data['category_id'];
            $item->category_parent_id = (int)$data['category_parent_id'];
            $item->category_name = $data['category_name'];
        } else {
            //достаём наименование категории и родительскую категорию
            $query = $db->getQuery(true)
                ->select('category_name, category_parent_id')
                ->from('#__hikashop_category')
                ->where('category_id = ' . $db->quote($this->state->get('category.id')));
            $data = $db->setQuery($query)->loadAssoc();
            $item->category_name = $data['category_name'];
            $item->category_parent_id = (int)$data['category_parent_id'];
        }

        //Если нет параметров яндекс.маркет, то их следует заполнить значениями по умолчанию из конфига расширения
        if (empty($item->params) || !is_array($item->params)) {
            $params = JComponentHelper::getParams('com_yandexmarket');
            $item->params['offer_type'] = $params['offer_type'];
            $item->params['typePrefix'] = $params['typePrefix'];
            $item->params['typePrefix-select'] = $params['typePrefix-select'];
            $item->params['typePrefix-field-select'] = $params['typePrefix-field-select'];
            $item->params['typePrefix-characteristic-select'] = $params['typePrefix-characteristic-select'];
            $item->params['vendor'] = $params['vendor'];
            $item->params['vendor-select'] = $params['vendor-select'];
            $item->params['vendor-field-select'] = $params['vendor-field-select'];
            $item->params['vendor-characteristic-select'] = $params['vendor-characteristic-select'];
            $item->params['model'] = $params['model'];
            $item->params['model-select'] = $params['model-select'];
            $item->params['basicName'] = $params['basicName'];
            $item->params['basicName-select'] = $params['basicName-select'];
            $item->params['description'] = $params['description'];
            $item->params['barcode'] = $params['barcode'];
            $item->params['barcode-select'] = $params['barcode-select'];
            $item->params['barcode-field-select'] = $params['barcode-field-select'];
            $item->params['param'] = $params['param'];
            $item->params['param-select'] = $params['param-select'];
            $item->params['weight'] = $params['weight'];
            $item->params['dimensions'] = $params['dimensions'];
            $item->params['rec'] = $params['rec'];
            $item->params['group'] = $params['group'];
            $item->params['available'] = $params['available'];
            $item->params['cbid'] = $params['cbid'];
            $item->params['bid'] = $params['bid'];
            $item->params['fee'] = $params['fee'];
            $item->params['store'] = $params['store'];
            $item->params['pickup'] = $params['pickup'];
            $item->params['delivery'] = $params['delivery'];
            $item->params['offers-delivery-cost-1'] = $params['offers-delivery-cost-1'];
            $item->params['offers-delivery-days-from-1'] = $params['offers-delivery-days-from-1'];
            $item->params['offers-delivery-days-to-1'] = $params['offers-delivery-days-to-1'];
            $item->params['offers-delivery-order-before-1'] = $params['offers-delivery-order-before-1'];
            $item->params['offers-delivery-cost-2'] = $params['offers-delivery-cost-2'];
            $item->params['offers-delivery-days-from-2'] = $params['offers-delivery-days-from-2'];
            $item->params['offers-delivery-days-to-2'] = $params['offers-delivery-days-to-2'];
            $item->params['offers-delivery-order-before-2'] = $params['offers-delivery-order-before-2'];
            $item->params['offers-delivery-cost-3'] = $params['offers-delivery-cost-3'];
            $item->params['offers-delivery-days-from-3'] = $params['offers-delivery-days-from-3'];
            $item->params['offers-delivery-days-to-3'] = $params['offers-delivery-days-to-3'];
            $item->params['offers-delivery-order-before-3'] = $params['offers-delivery-order-before-3'];
            $item->params['offers-delivery-cost-4'] = $params['offers-delivery-cost-4'];
            $item->params['offers-delivery-days-from-4'] = $params['offers-delivery-days-from-4'];
            $item->params['offers-delivery-days-to-4'] = $params['offers-delivery-days-to-4'];
            $item->params['offers-delivery-order-before-4'] = $params['offers-delivery-order-before-4'];
            $item->params['offers-delivery-cost-5'] = $params['offers-delivery-cost-5'];
            $item->params['offers-delivery-days-from-5'] = $params['offers-delivery-days-from-5'];
            $item->params['offers-delivery-days-to-5'] = $params['offers-delivery-days-to-5'];
            $item->params['offers-delivery-order-before-5'] = $params['offers-delivery-order-before-5'];
            $item->params['sales_notes'] = $params['sales_notes'];
            $item->params['min-quantity'] = $params['min-quantity'];
            $item->params['step-quantity'] = $params['step-quantity'];
            $item->params['manufacturer_warranty'] = $params['manufacturer_warranty'];
            $item->params['country_of_origin'] = $params['country_of_origin'];
            $item->params['adult'] = $params['adult'];
            $item->params['age'] = $params['age'];
        }

        return $item;
    }
} //YandexMarketModelCategory
