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

// Загружаем тултипы.
JHtml::_('bootstrap.tooltip');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.formvalidator');
JHTML::_('behavior.modal', 'a.modal');

JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task) {
		if (task == "cancel" || document.formvalidator.isValid(document.getElementById("adminForm"))) {
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
	};
');

if(!isset($this->input)) {
    $this->input = JFactory::getApplication()->input;
}
?>

    <div class="tab-description alert alert-info">
        <span class="icon-info"></span>
        <?php echo JText::_('COM_YANDEXMARKET_MY_CATEGORY_NAME_DESC'); ?>
    </div>

    <form
            id="form_category_myname_<?=$this->input->get('category_id');?>"
            class="form-inline"
            action="<?php echo JRoute::_('index.php?option=com_yandexmarket&task=categories.saveCategoryMyName')?>"
            method="post">

        <div class="form-group">
            <?php foreach ($this->form->getFieldset('myname') as $field) : ?>
                <?php echo $field->label; ?>
                <?php echo $field->input; ?>
            <?php endforeach; ?>
            <input type="button" onclick="saveCatMyName(<?=(int)$this->input->get('category_id');?>)" class="btn btn-default" value="<?php echo JText::_('JSAVE');?>">
        </div>
        <?php echo JHtml::_('form.token'); ?>
    </form>
</body>
</html>
