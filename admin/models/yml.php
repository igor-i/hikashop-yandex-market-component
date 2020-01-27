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
 * Модель Yml.
 * @since 0.1
 */
class YandexMarketModelYml extends JModelAdmin {
    /**
     * YandexMarketModelYml constructor.
     * @param array $config
     * @since 0.1
     */
    function __construct($config = array()) {
        parent::__construct($config);
        require_once (rtrim(JPATH_ADMINISTRATOR,DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'mainconnector.php');
        require_once (rtrim(JPATH_ADMINISTRATOR,DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'hikashop.php');
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
    public function getTable($type = 'yml', $prefix = 'YandexMarketTable', $config = array()) {
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
            $this->option . '.yml', 'yml', array('control' => 'jform', 'load_data' => $loadData)
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
        $data = JFactory::getApplication()->getUserState($this->option . '.edit.yml.data', array());

        //если в сессии данных нет, то нужно достать их из базы
        if (empty($data))  {
            $data = $this->getItem();

            //если поле id не заполнено, значит из базы ничего не достали, а следовательно это новая запись,
            //в которой парметры яндекс.маркет следует заполнить значениями по умолчанию из конфига расширения
            if (empty($data->id)) {
                //достаём значения полей формы из конфига и подставляем в $data
                $params = JComponentHelper::getParams('com_yandexmarket');
                $yaParams = $params->get('params');

                foreach ($yaParams as $k=>$v) {
                    $data->params[$k] = $v;
                }
            }
        }

        return $data;
    }
} //YandexMarketModelYml
