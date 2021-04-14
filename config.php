<?php
define('PSEUDO_FIELD_SBER_TAX_SYSTEM', 2324);
define('EDITOR_TEXT_SBER_TAX_SYSTEM', 2334);
define('PSEUDO_FIELD_SBER_TAX', 2325);
define('EDITOR_TEXT_SBER_TAX', 2335);

if (class_exists("\Sale\Payment")) {
    \Sale\Payment::addGateway('\SalePaymentSber\Gateway');
}

if (isset($_GET['orderId'])) {
    
    try {
        $order = \Sale\Order::getByTransaction($_GET['orderId']);
        $gateway = $order->getPaymentGateway();
        if ($gateway) {
            $res = $gateway->checkStatus($_GET['orderId']);  
        }        
    }
    catch(\Exception $e) {}
}

if ($this->getBo()) {

    $this->getBo()->addEditor(array(
        'id'    => EDITOR_TEXT_SBER_TAX_SYSTEM,
        'alias' => 'editor_text_SBER_tax_system',
        'name'  => $t->_('Редактор Sber СНО')
    ));

    $this->getBo()->addPseudoField(array(
        'id'       => PSEUDO_FIELD_SBER_TAX_SYSTEM,
        'original' => FIELD_TEXT,
        'len'      => 1,
        'name'     => $t->_('Sber СНО')
    ));

    $this->getBo()->addFieldEditor(PSEUDO_FIELD_SBER_TAX_SYSTEM, EDITOR_TEXT_SBER_TAX_SYSTEM);
	
    $this->getBo()->addEditor(array(
        'id'    => EDITOR_TEXT_SBER_TAX,
        'alias' => 'editor_text_SBER_tax',
        'name'  => $t->_('Редактор Sber НДС')
    ));

    $this->getBo()->addPseudoField(array(
        'id'       => PSEUDO_FIELD_SBER_TAX,
        'original' => FIELD_TEXT,
        'len'      => 1,
        'name'     => $t->_('Sber НДС')
    ));

    $this->getBo()->addFieldEditor(PSEUDO_FIELD_SBER_TAX, EDITOR_TEXT_SBER_TAX);	
}

function editor_text_SBER_tax_system_draw($field_def, $fieldvalue)
{
    ?>
    Ext.create('Ext.form.ComboBox',{
		fieldLabel: '<?= $field_def['describ'] ?>',
		name: '<?= $field_def['name'] ?>',
		allowBlank:<?= ($field_def['required'] ? 'false' : 'true') ?>,
		value: '<?= str_replace("\r", '\r', str_replace("\n", '\n', addslashes($fieldvalue))) ?>',
		editable: false,
		valueField: 'code',
		displayField: 'value',
		store: new Ext.data.SimpleStore({
			fields: ['code', 'value'],
			data : [['0', _('общая СН')], ['1', _('упрощенная СН (доходы)')], ['2', _('упрощенная СН (доходы минус расходы)')], ['3', _('единый налог на вмененный доход')], ['4', _('единый сельскохозяйственный налог')], ['5', _('патентная СН')],]
		}),
		defaultValue: '1'
    })
    <?
    return 28;
}

function editor_text_SBER_tax_draw($field_def, $fieldvalue)
{
    ?>
    Ext.create('Ext.form.ComboBox',{
		fieldLabel: '<?= $field_def['describ'] ?>',
		name: '<?= $field_def['name'] ?>',
		allowBlank:<?= ($field_def['required'] ? 'false' : 'true') ?>,
		value: '<?= str_replace("\r", '\r', str_replace("\n", '\n', addslashes($fieldvalue))) ?>',
		editable: false,
		valueField: 'code',
		displayField: 'value',
		store: new Ext.data.SimpleStore({
			fields: ['code', 'value'],
			data : [['0', _('без НДС')], ['1', _('НДС по ставке 0%')], ['2', _('НДС чека по ставке 10%')], ['3', _('НДС чека по ставке 18%')], ['4', _('НДС чека по расчетной ставке 10/110')], ['5', _('НДС чека по расчетной ставке 18/118')], ['6', _('НДС чека по ставке 20%')], ['7', _('НДС чека по расчетной ставке 20/120')]]
		}),
		defaultValue: '1'
    })
    <?
    return 28;
}