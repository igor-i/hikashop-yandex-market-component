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

// Подключаем библиотеку controllerform Joomla.
jimport('joomla.application.component.controllerform');

/**
 * Yml контроллер.
 * @since 0.1
 */
class YandexMarketControllerYml extends JControllerForm {

    /**
     * Method override to check if the user can edit an existing record.
     *
     * @param    array    An array of input data.
     * @param    string   The name of the key for the primary key.
     * @return   boolean
     * @since 0.1
     */
    protected function _allowEdit($data = array(), $key = 'id') {
        // Initialise variables.
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;

        // Assets are being tracked, so no need to look into the category.
        return \JFactory::getUser()->authorise('core.edit', 'com_yandexmarket.yml.' . $recordId);
    }

    /**
     * Mark the yml as default
     * @since 0.1
     */
    public function setAsDefault() {
        $cid = \JFactory::getApplication()->input->get('cid', array(), 'array');

        if (isset($cid[0])) {
            // Cleanup the is_default field
            $db = \JFactory::getDbo();

            $query = $db->getQuery(true)
                ->set('is_default = 0')
                ->update('#__yandexmarket_ymls');
            $db->setQuery($query)->execute();

            // Set the yml as default
            $model = $this->getModel();
            $row   = $model->getTable();

            $row->load($cid[0]);
            $row->save(
                array(
                    'is_default' => true
                )
            );
        }

        $this->setRedirect('index.php?option=com_yandexmarket&view=ymls');
    }
} //YandexMarketControllerYml
