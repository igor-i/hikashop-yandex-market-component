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
// Загружаем проверку формы и ещё какие-то украшательства
if (version_compare(JVERSION, '3.0.0', 'ge')) {
    JHtml::_('behavior.formvalidator');
    JHtml::_('formbehavior.chosen', 'select');
} else {
    JHtml::_('behavior.formvalidation');
}
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');
// Стили
//JHtml::stylesheet('media/com_yandexmarket/css/admin.min.css');

$input = JFactory::getApplication()->input;
JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task) {
		if (task == "category.cancel" || document.formvalidator.isValid(document.getElementById("adminForm"))) {
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
	};
');
?>
<form
        action="<?php echo JRoute::_('index.php?option=com_yandexmarket&view=category&layout=edit&category_id=' . (int)$this->item->category_id); ?>"
        method="post"
        name="adminForm"
        id="adminForm"
        class="form-validate category">

    <div class="form-inline form-inline-header">
        <?php echo $this->form->renderField('category_name'); ?>
        <?php echo $this->form->renderField('category_myname'); ?>
        <?php echo $this->form->renderField('category_menuitem'); ?>
    </div>

    <div class="row-fluid">
        <div class="span9">
            <div class="form-horizontal">
                <?php if (version_compare(JVERSION, '3.0.0', 'ge')) { ?>
                    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'offers')); ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'offers', JText::_('COM_YANDEXMARKET_HEADING_OFFERS_SETTINGS', true)); ?>
                    <div>
                        <?php echo $this->form->renderFieldset('offers'); ?>
                    </div>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
                <?php } else { ?>
                    <?php echo JHtml::_('tabs.start', 'myTab', array('useCookie' => 1, 'active' => 'offers')); ?>
                    <?php echo JHtml::_('tabs.panel', JText::_('COM_YANDEXMARKET_HEADING_OFFERS_SETTINGS'), 'offers'); ?>
                    <div class="width-60 fltlft">
                        <fieldset class="adminform">
                            <ul class="adminformlist">
                                <?php foreach ($this->form->getFieldset('offers') as $field) : ?>
                                    <li><?php echo $field->label; ?>
                                        <?php echo $field->input; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </fieldset>
                    </div>
                    <div style="clear: both"></div>
                <?php } ?>
                <?php if (version_compare(JVERSION, '3.0.0', 'ge')) { ?>
                    <?php echo JHtml::_('bootstrap.endTabSet'); ?>
                <?php } else { ?>
                    <div style="clear: both"></div>
                    <?php echo JHtml::_('tabs.end'); ?>
                <?php } ?>
            </div>
        </div>

        <div class="span3">
            <?php
            // Set main fields.
            $this->fields = array(
                'published',
                'is_default'
            );
            ?>
            <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
        </div>
    </div>

    <input type="hidden" name="task" value=""/>
    <?php echo JHtml::_('form.token'); ?>
</form>
