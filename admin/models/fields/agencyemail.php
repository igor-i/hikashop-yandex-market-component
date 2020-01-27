<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */

defined('_JEXEC') or die('Restricted access');

class JFormFieldAgencyemail extends JFormField {
    public $type = 'Agencyemail';
    private $dataConfig;

    public function getInput() {
        $dataConfig = $this->form->getData();
        $this->dataConfig = new JRegistry($dataConfig->get('params'));

        // Достаём E-mail техподдержки из конфига компонента или из глобального конфига Joomla
        if (!empty($this->dataConfig->get('email'))) {
            $email = $this->dataConfig->get('email');
        } else {
            $app = JFactory::getApplication();
            $email = $app->get('mailfrom');
        }

        $html = "<input
                    id='jform_params_email'
                    name='jform[params][email]'
                    class='validate-email'
                    type='email'
                    value='$email'>";

        return $html;
    }
} //JFormFieldAgencyemail
