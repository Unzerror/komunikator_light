/*
 *  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 
 *    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
 *    Copyright (C) 2012-2013, ООО «Телефонные системы»
 
 *    ЭТОТ ФАЙЛ является частью проекта «Komunikator»
 
 *    Сайт проекта «Komunikator»: http://komunikator.ru/
 *    Служба технической поддержки проекта «Komunikator»: E-mail: support@komunikator.ru
 
 *    В проекте «Komunikator» используются:
 *      исходные коды проекта «YATE», http://yate.null.ro/pmwiki/
 *      исходные коды проекта «FREESENTRAL», http://www.freesentral.com/
 *      библиотеки проекта «Sencha Ext JS», http://www.sencha.com/products/extjs
 
 *    Web-приложение «Komunikator» является свободным и открытым программным обеспечением. Тем самым
 *  давая пользователю право на распространение и (или) модификацию данного Web-приложения (а также
 *  и иные права) согласно условиям GNU General Public License, опубликованной
 *  Free Software Foundation, версии 3.
 
 *    В случае отсутствия файла «License» (идущего вместе с исходными кодами программного обеспечения)
 *  описывающего условия GNU General Public License версии 3, можно посетить официальный сайт
 *  http://www.gnu.org/licenses/ , где опубликованы условия GNU General Public License
 *  различных версий (в том числе и версии 3).
 
 *  | ENG | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 
 *    "Komunikator" is a web interface for IP-PBX "YATE" configuration and management
 *    Copyright (C) 2012-2013, "Telephonnyie sistemy" Ltd.
 
 *    THIS FILE is an integral part of the project "Komunikator"
 
 *    "Komunikator" project site: http://komunikator.ru/
 *    "Komunikator" technical support e-mail: support@komunikator.ru
 
 *    The project "Komunikator" are used:
 *      the source code of "YATE" project, http://yate.null.ro/pmwiki/
 *      the source code of "FREESENTRAL" project, http://www.freesentral.com/
 *      "Sencha Ext JS" project libraries, http://www.sencha.com/products/extjs
 
 *    "Komunikator" web application is a free/libre and open-source software. Therefore it grants user rights
 *  for distribution and (or) modification (including other rights) of this programming solution according
 *  to GNU General Public License terms and conditions published by Free Software Foundation in version 3.
 
 *    In case the file "License" that describes GNU General Public License terms and conditions,
 *  version 3, is missing (initially goes with software source code), you can visit the official site
 *  http://www.gnu.org/licenses/ and find terms specified in appropriate GNU General Public License
 *  version (version 3 as well).
 
 *  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

Ext.apply(Ext.form.field.VTypes, {
    mailVal: function(val, field) {
        if (/^(\w([-_+.']?\w+)+@(\w(-*\w+)+\.)+[a-zA-Z]{2,4}[,;])*\w([-_+.']?\w+)+@(\w(-*\w+)+\.)+[a-zA-Z]{2,4}$/.test(val))
        {
            return true;
        }
        return false;
    },
    mailValText: app.msg.invalid_email
});

Ext.define('app.module.Mail_Settings_Panel', {
    extend: 'Ext.form.Panel',
    hidden: true,
    url: 'data.php?action=mail_settings',
    // autoLoad: true,
    style: 'padding: 40px;',
    autoScroll: true,
    frame: true,
    waitMsgTarget: true,
    fieldDefaults: {
        labelAlign: 'right',
        labelWidth: 260,
        msgTarget: 'side'
    },
    buttons: [{
            monitorValid: true,
            formBind: true,
            text: app.msg.save, // Сохранить
            // action: 'save',

            handler: function() {
                var form = this.ownerCt.ownerCt.getForm();

                console.log(form.getValues());

                if (form.isValid()) {
                    form.submit();
                }
            }

        },
        {
            text: app.msg.load, // Загрузка

            handler: function() {
                var form = this.ownerCt.ownerCt.getForm();

                form.load();
            }

        }],
    initComponent: function() {

        this.items = [
            {
                title: app.msg.mailevents, // События, приводящие к отправке уведомлений
                xtype: 'fieldset',
                defaultType: 'checkboxfield',
                defaults: {
                    inputValue: 'true',
                    uncheckedValue: 'false'
                },
                items: [
                    {
                        boxLabel: app.msg.mailevent_incoming_gate,
                        name: 'incoming_trunk'
                    }, {
                        boxLabel: app.msg.mailevent_incoming,
                        name: 'incoming_call'
                    }, {
                        boxLabel: app.msg.mailevent_outgoing,
                        name: 'outgoing_call'
                    }, {
                        boxLabel: app.msg.mailevent_internal,
                        name: 'internal_call'
                    }, {
                        boxLabel: app.msg.mailevent_unanswered_incoming_call,
                        name: 'unanswered_incoming_call'
                    }, {
                        boxLabel: app.msg.mailevent_unanswered_order_call,
                        name: 'unanswered_order_call'
                    }
                ]
            }, {
                title: app.msg.mail_nofications, // Почтовые уведомления
                xtype: 'fieldset',
                border: true,
                items: [
                    {
                        xtype: 'textfield',
                        anchor: '100%',
                        name: 'from',
                        fieldLabel: app.msg.from,
                        vtype: 'email'
                    },
                    {// пароль для доступа к ЭПЯ
                        xtype: 'textfield',
                        anchor: '100%',
                        name: 'password',
                        fieldLabel: app.msg.password,
                        inputType: 'password'
                    },
                    {
                        xtype: 'textfield',
                        anchor: '100%',
                        name: 'fromname',
                        fieldLabel: app.msg.fromname
                    },
                    {
                        xtype: 'textfield',
                        anchor: '100%',
                        name: 'email',
                        fieldLabel: app.msg.email,
                        vtype: 'mailVal'
                    },
                    {
                        xtype: 'textareafield',
                        anchor: '100%',
                        name: 'incoming_call_text',
                        fieldLabel: app.msg.mail1
                    },
                    {
                        xtype: 'textareafield',
                        anchor: '100%',
                        name: 'incoming_trunk_text',
                        fieldLabel: app.msg.mail2
                    },
                    {
                        xtype: 'textfield',
                        anchor: '100%',
                        name: 'time_zone_hour',
                        fieldLabel: app.msg.time_zone_hour
                    },
                    {
                        title: app.msg.mail_subject, // Тема сообщения
                        xtype: 'fieldset',
                        border: true,
                        items: [
                            {
                                xtype: 'textfield',
                                anchor: '100%',
                                name: 'incoming_subject',
                                fieldLabel: app.msg.mail_incoming_subject
                            },
                            {
                                xtype: 'textfield',
                                anchor: '100%',
                                name: 'outgoing_subject_call_not_accepted',
                                fieldLabel: app.msg.mail_outgoing_subject_call_not_accepted
                            },
                            {
                                hidden: true,
                                xtype: 'textfield',
                                anchor: '100%',
                                name: 'outgoing_subject_fax_not_accepted',
                                fieldLabel: app.msg.mail_outgoing_subject_fax_not_accepted
                            },
                            {
                                xtype: 'textfield',
                                anchor: '100%',
                                name: 'outgoing_subject_call_accepted',
                                fieldLabel: app.msg.mail_outgoing_subject_call_accepted
                            },
                            {
                                hidden: true,
                                xtype: 'textfield',
                                anchor: '100%',
                                name: 'outgoing_subject_fax_accepted',
                                fieldLabel: app.msg.mail_outgoing_subject_fax_accepted
                            }
                        ]
                    }
                ]
            }
        ];

        this.callParent(arguments);

        var form = this.getForm();

        form.load();
    }
});