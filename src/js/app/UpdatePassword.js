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

Ext.apply(Ext.form.VTypes, {
    password: function(val, field) {
        if (field.initialPassField) {
            var pwd = Ext.getCmp(field.initialPassField);
            return (val == pwd.getValue());
        }
        return true;
    },
    passwordCheckText: app.msg.warning_pwd
});

Ext.define('app.UpdatePassword', {
    extend: 'Ext.window.Window',
    //alias : 'widget.login',
    id: 'UpdatePassword',
    autoShow: true,
    width: 300,
    height: 200,
    layout: 'border',
    border: false,
    modal: true,
    closable: true, //убирает крестик, закрывающий окно
    resizable: false, // нельзя изменить размеры окна
    draggable: false, //перемещение объекта по экрану
    //closeAction: 'hide',

    initComponent: function() {
        this.items = [{
                id: 'update_password',
                title: app.msg.update_password, //получаем название титула окна
                region: 'center', //расположена форма по центру
                xtype: 'form',
                method: 'POST',
                bodyStyle: 'padding:10px; background: transparent;border-top: 0px none;',
                labelWidth: 100,
                defaultType: 'textfield',
                items: [/*{
                 fieldLabel: app.msg.login,
                 name: 'ExtensionChange',
                 id: 'ExtensionChange',
                 allowBlank: false
                 },*/ {
                        fieldLabel: app.msg.password,
                        name: 'pass',
                        inputType: 'password',
                        id: 'pass',
                        allowBlank: false,
                        listeners:
                                {
                                    specialkey: function(t, e) {
                                        var change_pass = Ext.getCmp('change_pass');
                                        if (e.getKey() == e.ENTER && !change_pass.disabled) {
                                            e.stopEvent();
                                            change_pass.handler();
                                        }
                                    }
                                }
                    },
                    {
                        fieldLabel: app.msg.new_password, //новый пароль
                        name: 'passwd',
                        inputType: 'password',
                        id: 'passwd',
                        allowBlank: false,
                        height: 20,
                        vtype: 'password',
                        regex: /^\d{3,10}$/
                    },
                    {
                        fieldLabel: app.msg.repeat_new_password, //повторить новый пароль
                        name: 'newpasswd',
                        inputType: 'password',
                        id: 'newpasswd',
                        allowBlank: false,
                        vtype: 'password',
                        initialPassField: 'passwd',
                        height: 20,
                        regex: /^\d{3,10}$/
                    },
                    {
                        name: 'action',
                        value: 'change_password',
                        hidden: true
                    }
                ]
            }
        ];

        this.buttons = [{
                id: 'change_pass',
                text: app.msg.save,
                handler: function() {
                    var update_password = Ext.getCmp('update_password');
                    if (update_password.getForm().isValid()) {
                        update_password.body.mask();
                        app.request(
                                update_password.getForm().getValues(),
                                function(result) {
                                    update_password.getForm().reset();
                                    Ext.getCmp('UpdatePassword').close();
                                    update_password.body.unmask();
                                }, function(result) {
                            update_password.body.unmask();
                        });
                    }
                }
            },
            {
                text: app.msg.cancel,
                scope: this,
                handler: this.close
            }];
        this.callParent(arguments);
    }
});