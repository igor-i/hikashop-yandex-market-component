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

class MyLibHelper
{
    public static $extension = 'com_yandexmarket';

    /**
     * Подключает файл со стилями
     * @param $path
     * @return string
     * @since 0.1
     */
    public static function getStyle($path)
    {
        $html = '';
        if (file_exists($path)) {
            $style = file_get_contents($path);
            $html .= '<style>' . $style . '</style>';
        }

        return $html;
    }

    /**
     * Очищает строку от тегов и повторяющихся пробелов, в режиме $xhtml=true оставляет разрешённые в xhtml-разметке теги
     *
     * @param $string
     * @param bool $xhtml
     * @return string
     * @since 0.2.1
     */
    public static function clearString($string, $xhtml = false)
    {
        if ($xhtml) {
            // Убираем повторяющиеся пробемы и теги, за исключением разрешённых в xhtml
            $result = trim(strip_tags($string, '<h3><ul><li><p><br/>'));
            // Заменяем кавычки и т.п. на соответствующие коды
            $result = htmlspecialchars($result, ENT_QUOTES | ENT_XML1, 'UTF-8');
            // Добавляем блок <![CDATA[...]]>
            $result = '<![CDATA[' . $result . ']]>';
        } else {
            // Убираем повторяющиеся пробемы и теги
            $result = trim(strip_tags($string));
            // Заменяем кавычки и т.п. на соответствующие html-коды
            $result = htmlspecialchars($result, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        return $result;
    }

    /**
     * Преобразует массив параметров, которые нужно передать POST, в кодированную строку
     * @param $parameters
     * @return string
     * @since
     */
    public static function setUrlParams($parameters)
    {
        $string = '';
        foreach ($parameters as $key => $value) {
            $string .= $key . '=' . urlencode($value) . '&';
        }
        return $string;
    }

    /**
     * С помощью этой функции отправляем запросы к серверу СДЭК
     * @param $url
     * @param $value - url-закодированная строка с параметрами ('para1=val1&para2=val2&)
     * @return mixed
     * @since
     */
    public static function fileGetContentsCurl($url, $value)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // передавать будем http-методом post
        curl_setopt($ch, CURLOPT_POST, 1);
        // устанавливаем передаваемые постом поля url-закодированной строки, наподобие 'para1=val1&para2=val2&...'
        curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
        // Set curl to return the data instead of printing it to the browser
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        // не проверять SSL сертификат
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // не проверять Host SSL сертификата
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // это необходимо, чтобы cURL не высылал заголовок на ожидание
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
} //Base
