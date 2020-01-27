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
JHtml::_('behavior.keepalive');
// Загружаем проверку формы и ещё какие-то украшательства
if (version_compare(JVERSION, '3.0.0', 'ge')) {
    JHtml::_('behavior.formvalidator');
    JHtml::_('formbehavior.chosen', 'select', null, array('disable_search_threshold' => 0 ));
} else {
    JHtml::_('behavior.formvalidation');
}
// Стили
//JHtml::stylesheet('media/com_yandexmarket/css/admin.min.css');

$input = JFactory::getApplication()->input;
JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task) {
		if (task == "yml.cancel" || document.formvalidator.isValid(document.getElementById("adminForm"))) {
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
	};
');
?>
<form
        action="<?php echo JRoute::_('index.php?option=com_yandexmarket&view=yml&layout=edit&id=' . (int)$this->item->id); ?>"
        method="post"
        name="adminForm"
        id="adminForm"
        class="form-validate yml">

    <div class="form-inline form-inline-header">
        <?php echo $this->form->renderField('name'); ?>
        <?php echo $this->form->renderField('yml_menuitem'); ?>
    </div>
    <div class="row-fluid">
        <div class="span9">
            <div class="form-horizontal">
                <?php if (version_compare(JVERSION, '3.0.0', 'ge')) { ?>
                    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'yandex')); ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'yandex', JText::_('COM_YANDEXMARKET_FIELDSET_YANDEX_SETTINGS_LABEL', true)); ?>
                    <div>
                        <?php echo $this->form->renderFieldset('yandex'); ?>
                    </div>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
                <?php } else { ?>
                    <?php echo JHtml::_('tabs.start', 'myTab', array('useCookie' => 1, 'active' => 'yandex')); ?>
                    <?php echo JHtml::_('tabs.panel', JText::_('COM_YANDEXMARKET_FIELDSET_YANDEX_SETTINGS_LABEL'), 'yandex'); ?>
                    <div class="width-60 fltlft">
                        <fieldset class="adminform">
                            <ul class="adminformlist">
                                <?php foreach ($this->form->getFieldset('yandex') as $field) : ?>
                                    <li><?php echo $field->label; ?>
                                        <?php echo $field->input; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </fieldset>
                    </div>
                    <div style="clear: both"></div>
                <?php } ?>

                <?php if (version_compare(JVERSION, '3.0.0', 'ge')) { ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'offers', JText::_('COM_YANDEXMARKET_YML_OFFERINGS_LABEL', true)); ?>
                    <div class="tab-description alert alert-info">
                        <span class="icon-info"></span><?php echo JText::_('COM_YANDEXMARKET_YML_EDIT_DESC'); ?>
                    </div>
                    <div>
                        <?php $offersField = $this->form->getField('offers_settings'); ?>
                        <?php echo $offersField->label; ?>
                        <?php echo $offersField->input; ?>
                    </div>
                <?php } else { ?>
                <?php echo JHtml::_('tabs.panel', JText::_('COM_YANDEXMARKET_YML_OFFERINGS_LABEL'), 'offerings'); ?>
                    <div>
                        <?php $offersField = $this->form->getField('offers_settings'); ?>
                        <?php echo $offersField->label; ?>
                        <?php echo $offersField->input; ?>
                    </div>
                <?php } ?>

                <?php if (version_compare(JVERSION, '3.0.0', 'ge')) { ?>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
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
