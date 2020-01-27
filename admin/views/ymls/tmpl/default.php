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

//JHtml::addIncludePath(YANDEXMARKET_ADMIN_PATH . '/helpers/html');
// Загружаем тултипы.
JHtml::_('bootstrap.tooltip');
JHtml::_('formbehavior.chosen', 'select');
JHtml::stylesheet('media/com_yandexmarket/css/admin.min.css');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDir   = $this->escape($this->state->get('list.direction'));
?>
<form
        action="<?php echo JRoute::_('index.php?option=com_yandexmarket&view=ymls'); ?>"
        method="post"
        name="adminForm"
        id="adminForm">

    <?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
    <?php if (empty($this->items)) : ?>
        <div class="alert alert-no-items">
            <?php echo JText::_('COM_YANDEXMARKET_NO_MATCHING_RESULTS') . '<br/>' . JText::_('COM_YANDEXMARKET_REQUIRED_SETTING'); ?>
        </div>
    <?php else : ?>
        <div id="j-main-container">
            <table class="adminlist table table-striped" id="ymlList">
                <thead>
                <tr>
                    <th width="1%">
                        <?php echo JHtml::_('grid.checkall'); ?>
                    </th>

                    <th width="1%" style="min-width:55px" class="nowrap center">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_STATUS',
                            'yml.published',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th class="title">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_NAME',
                            'yml.name',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th class="title">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_MENU_ITEM',
                            'yml.yml_menuitem',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th width="8%" class="nowrap center">
		                <?php echo JText::_('COM_YANDEXMARKET_HEADING_NUM_OFFERS'); ?>
                    </th>

                    <th width="60" class="center">
                        <?php echo JText::_('COM_YANDEXMARKET_HEADING_YML_LINKS'); ?>
                    </th>

                    <th width="260" class="center">
		                <?php echo JText::_('COM_YANDEXMARKET_HEADING_YML_ACTIONS'); ?>
                    </th>

                    <th width="8%" class="nowrap center">
		                <?php echo JText::_('COM_YANDEXMARKET_HEADING_YML_UPDATE_FILE'); ?>
                    </th>

                    <th width="1%" class="nowrap">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_ID',
                            'yml.id',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>
                </tr>
                </thead>

                <tbody>
                <?php
                foreach ($this->items as $i => $this->item) :
                    $editLink = JRoute::_('index.php?option=com_yandexmarket&view=yml&layout=edit&id=' . $this->item->id);
                    ?>
                    <tr class="<?php echo 'row' . ($i % 2); ?>">
                        <td class="center">
                            <?php echo JHtml::_('grid.id', $i, $this->item->id); ?>
                        </td>

                        <td class="center">
                            <div class="btn-group">
                                <?php
                                echo JHtml::_(
                                    'jgrid.published',
                                    $this->item->published,
                                    $i,
                                    'ymls.'
                                );
                                ?>
                                <a
                                        href="#"
                                        onclick="return listItemTask('cb<?php echo $i; ?>','yml.setAsDefault')"
                                        class="btn btn-micro hasTooltip"
                                        title=""
                                        data-original-title="Toggle default status.">
                                    <?php
                                    echo sprintf(
                                        '<span class="icon-%s"></span>',
                                        $this->item->is_default ? 'featured' : 'unfeatured'
                                    );
                                    ?>
                                </a>
                            </div>
                        </td>

                        <td class="nowrap">
                            <?php echo JHtml::_('link', $editLink, $this->escape($this->item->name)); ?>
                        </td>

                        <td class="nowrap yml_menuitem">
                            <small>
                                <?php
                                if (!empty($this->item->yml_menuitem)) {
                                    echo $this->escape($this->item->yml_menuitem) . ': ' . $this->escape($this->item->menuitem_title);
                                }
                                ?>
                            </small>
                        </td>

                        <td class="center">
                            <span class="badge <?php if ((int)$this->item->offers_count > 0) echo "badge-info"; ?>">
		                    <?php echo (int)$this->item->offers_count; ?>
                            </span>
                        </td>

                        <td class="nowrap center yandexmarket-links">
                            <?php echo $this->loadTemplate('previews'); ?>
                        </td>

                        <td class="center">
		                    <?php echo $this->loadTemplate('remake'); ?>
                        </td>

                        <td class="center">
		                    <?php if (!empty($this->item->created_on)) echo date('j M Y, H:i',strtotime($this->item->created_on)); ?>
                        </td>

                        <td class="center">
                            <?php echo (int)$this->item->id; ?>
                        </td>
                    </tr>
                    <?php
                endforeach;
                ?>
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="8">
                            <?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                </tfoot>

            </table>
        </div>
    <?php endif; ?>
    <div>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="ymlid" value=""/>
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
<?=$this->footer;?>
