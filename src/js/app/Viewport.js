/*
 *  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 
 *    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
 *    Copyright (C) 2012-2017, ООО «Телефонные системы»
 
 *    ЭТОТ ФАЙЛ является частью проекта «Komunikator»
 
 *    Сайт проекта «Komunikator»: http://4yate.ru/
 *    Служба технической поддержки проекта «Komunikator»: E-mail: support@4yate.ru
 
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
 *    Copyright (C) 2012-2017, "Telephonnyie sistemy" Ltd.
 
 *    THIS FILE is an integral part of the project "Komunikator"
 
 *    "Komunikator" project site: http://4yate.ru/
 *    "Komunikator" technical support e-mail: support@4yate.ru
 
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

Ext.define('app.Viewport', {
    id: 'Viewport',
    extend: 'Ext.container.Viewport',
    style: 'padding : 2px 10px', // отступы: верх, низ - 2; право, лево - 10
    layout: 'border',
    items: [{
            region: 'north', // верх
            // autoHeight : true,
            border: false,
            margins: '0 0 5 0'
        }, {
            region: 'south', // низ
            title: '<div style="text-align : center"><p style="font-size : 8pt">' + app.msg.copyright + '</p></div>', // Komunikator Copyright (C) 2012-2017, ООО «Телефонные системы»
            border: false,
            margins: '10 0 10 0'
        }, {
            region: 'west', // лево
            collapsible: true,
            title: app.msg.pbx_status, // Статус АТС
            // autoHeight : true,
            // height : 100,
            width: 270,
            // collapsed : true,

            listeners: {
                afterrender: function() {
                    this.fireEvent('expand', this);
                },
                expand: function(i) {
                    i.items.each(function(s) {
                        if (s && s.store) {
                            app.set_autorefresh(s, true);
                            // console.log('owner expand for: '+s.store.storeId);
                        }
                    })
                },
                collapse: function(i) {
                    i.items.each(function(s) {
                        if (s && s.store) {
                            app.set_autorefresh(s, false);
                            // console.log('owner collapse for: '+s.store.storeId);
                        }
                    })
                }
            },
            // split : true,
            items: [
                Ext.create('app.module.Status_Grid', {
                    title: app.msg.statistic  // Статистика АТС
                })
            ]

        }, {
            region: 'center', // центр
            layout: 'fit',
            xtype: 'tabpanel',
            id: 'main_tabpanel',
            bodyStyle: 'padding : 15px', // отступы: верх, низ, право, лево - 15
            // resizable : true,
            // split : true,
            // layout : 'anchor',
            // autoHeight : true,
            // layout : 'accordion',
            // style : 'padding : 2px',
            // defaults : {
            //     layout : 'fit'
            // },   
            // activeTab : 0,

            listeners: {
                afterrender: function() {
                    var f = this.setActiveTab(0);
                    // if (f && f.items) ;
                    // grid.ownerCt.layout.setActiveItem(grid.ownerCt.items.indexOf(grid));
                    // console.log(
                    f.items.items[0].fireEvent('activate', f.items.items[0]);  // f.setActiveItem(0);
                },
                tabchange: function(c, f, o) {
                    if (f && f.items) {
                        f.getLayout().setActiveItem(0);
                        f.items.items[0].fireEvent('activate', f.items.items[0]);  // f.setActiveItem(0);
                    }
                }
            },
            items: [
                // Ext.create('app.module.Attendant_Panel'),
                // Ext.create('app.module.Extensions_Panel'),

                Ext.create('app.Card_Panel', {
                    title: app.msg.directory, // Справочники
                    items: [
                        Ext.create('app.module.Extensions_Grid', {
                            title: app.msg.extensions  // Внутренние номера
                        }),
                        Ext.create('app.module.Groups_Grid', {
                            title: app.msg.groups  // Группы
                        }),
                        Ext.create('app.module.AddressBook_Grid', {
                            title: app.msg.address_book  // Адресная книга
                        })
                    ]
                }),
                Ext.create('app.Card_Panel', {
                    title: app.msg.attendant, // Автосекретарь
                    items: [
                        Ext.create('app.module.Prompts_Panel', {
                            // title: '<center>'+app.msg.prompts+'</center>'
                            title: app.msg.prompts  // Приветствия
                        }),
                        Ext.create('app.module.Keys_Grid', {
                            title: app.msg.keys  // Меню приветствия
                        }),
                        Ext.create('app.module.Time_Frames_Grid', {
                            title: app.msg.timeframes  // Расписание рабочего времени
                        })
                    ]
                }),
                Ext.create('app.Card_Panel', {
                    title: app.msg.routing, // Маршрутизация
                    items: [
                        Ext.create('app.module.DID_Grid', {
                            title: app.msg.routing_rules  // Правила маршрутизации
                        }),
                        Ext.create('app.module.Dial_plans_Grid', {
                            title: app.msg.dial_plans  // Правила набора номера
                        }),
                        Ext.create('app.module.Conferences_Grid', {
                            title: app.msg.conferences  // Конференции
                        }),
                        Ext.create('app.module.Gateways_Grid', {
                            title: app.msg.gateways  // Провайдеры
                        })
                    ]
                }),
                Ext.create('app.Card_Panel', {
                    title: app.msg.music_on_hold, // Музыка на удержании
                    items: [
                        Ext.create('app.module.Music_On_Hold_Grid', {
                            title: app.msg.music_on_hold  // Музыка на удержании
                        }),
                        Ext.create('app.module.Playlist_Grid', {
                            title: app.msg.playlist  // Плейлист
                        })
                    ]
                }),
                Ext.create('app.Card_Panel', {
                    title: app.msg.call_logs, // История звонков
                    items: [
                        Ext.create('app.module.Call_logs_Grid', {
                            title: app.msg.call_logs  // История звонков
                        }),
                        Ext.create('app.module.Active_calls_Grid', {
                            title: app.msg.active_calls  // Активные звонки
                        })
                    ]
                }),
                //------------временно------------------------
                /*
                 Ext.create('app.Card_Panel', {
                    title: "history2", // История звонков
                    items: [
                        Ext.create('app.module.Call_logs_Grid1', {
                            title: app.msg.call_logs  // История звонков
                        })
                    ]
                }),*/
                //----------------------------------------------
                
                
              /*  Ext.create('app.Card_Panel', {
                    title: app.msg.Call_Record_Grid, //запись разговора
                    items: [
                        Ext.create('app.module.Call_Record_Grid', {
                            title: app.msg.Call_Record_Grid  // запись разговора
                        }),
                    ]}),*/
                Ext.create('app.Card_Panel', {
                    title: app.msg.settings, // Настройки
                    items: [
                        Ext.create('app.module.Users_Grid', {
                            title: app.msg.users  // Управление доступом
                        }),
                        Ext.create('app.module.Network_Settings_Panel', {
                            title: app.msg.network_settings  // Сетевые настройки
                        }),/*
                        Ext.create('app.module.Tuning_Modules_Grid', {
                            title: app.msg.modules  // Модули
                        }),*/
                        Ext.create('app.module.Extra_Grid', {
                            title: app.msg.additional_settings  // Дополнительные настройки
                        }), /*
                          Ext.create('app.module.Call_Record_Grid', {
                            title: app.msg.Call_Record_Grid  // запись разговора
                        }),
                        
                         Ext.create('app.module.Ntn_Settings_Grid',{
                         title:app.msg.notification_settings  // Настройки уведомлений
                         }),
                         Ext.create('app.module.Update_Panel',{
                         title:app.msg.update  // Обновление
                         }),
                         */
                        {
                            title: app.msg.reboot_pbx, // Перезагрузка АТС

                            handler: function() {
                                var fn = function(btn) {
                                    if (btn == 'yes') {
                                        var box = Ext.MessageBox.wait(app.msg.wait_reboot, app.msg.performing_actions);
                                        // Пожалуйста, подождите пока происходит перезагрузка АТС
                                        // Выполнение действий
                                        app.request(
                                                {
                                                    action: 'reboot'
                                                },
                                        function(result) {
                                            if (!result.message)
                                                box.hide();
                                            // console.log(result)
                                        });
                                    }
                                };
                                Ext.MessageBox.show({
                                    title: app.msg.performing_actions, // Выполнение действий
                                    msg: app.msg.reboot_pbx_question, // Выполнить перезагрузку АТС?
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: fn,
                                    animEl: 'mb4',
                                    icon: Ext.MessageBox.QUESTION
                                });

                            }
                        },/*
                        {
                            title: app.msg.update, // Обновление

                            handler: function() {
                                var fn = function(btn) {
                                    if (btn == 'yes') {
                                        var box = Ext.MessageBox.wait(app.msg.wait_checkforupdates, app.msg.performing_actions);
                                        // Пожалуйста, подождите пока происходит проверка обновлений АТС
                                        // Выполнение действий
                                        app.request(
                                                {
                                                    action: 'checkforupdates'
                                                },
                                        function(result) {
                                            if (!result.message)
                                                box.hide();
                                            if (result.update_exists) {
                                                var fn_update = function(btn) {
                                                    if (btn == 'yes') {
                                                        var box = Ext.MessageBox.wait(app.msg.wait_update, app.msg.performing_actions);
                                                        // Пожалуйста, подождите пока происходит установка обновлений
                                                        // Выполнение действий
                                                        var polling_time = 5000;
                                                        // Ext.MessageBox.maxHeight = 400;  
                                                        var intervalID = setInterval(function() {
                                                            app.request(
                                                                    {
                                                                        action: 'get_update_out'
                                                                    },
                                                            function(result) {
                                                                if (result.data)
                                                                    box.updateText(app.msg.wait_update + '<br>' + result.data);
                                                            }
                                                            );

                                                        }, polling_time);
                                                        app.request(
                                                                {
                                                                    action: 'install_update'
                                                                },
                                                        function(result) {
                                                            clearInterval(intervalID);
                                                            if (!result.message)
                                                                box.hide();
                                                        }
                                                        );
                                                    }
                                                };
                                                Ext.MessageBox.show({
                                                    title: app.msg.performing_actions, // Выполнение действий
                                                    msg: app.msg.update_install + ' ' + result.update_exists + '?', // Найдено обновление. Установить обновление
                                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                                    fn: fn_update,
                                                    animEl: 'mb4',
                                                    icon: Ext.MessageBox.QUESTION
                                                });
                                            }
                                        });
                                    }
                                };
                                Ext.MessageBox.show({
                                    title: app.msg.performing_actions, // Выполнение действий
                                    msg: app.msg.checkforupdates, // Проверить на наличие обновлений?
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: fn,
                                    animEl: 'mb4',
                                    icon: Ext.MessageBox.QUESTION
                                });
                            }}*/
                    ]
                }),
                
                 /*Ext.create('app.Card_Panel', {
                 // id:'modules',
                 title: app.msg.modules, // Модули
                 items: [
                 Ext.create('app.module.Call_Record_Grid', {
                 title: app.msg.Call_Record_Grid  // запись разговора
                 }),
                 Ext.create('app.module.Mail_Settings_Panel', {
                 title: app.msg.mail_settings  // Почтовые уведомления
                 })
                 ]
                 })*/
                 
                /*
                 { 
                 title: app.msg.attendant,
                 layout: 'anchor', 
                 items: [
                 { height: 100, border: false, html: 'test message' },
                 Ext.create('app.module.Prompts_Grid',
                 { height: 300 }
                 )
                 ]
                 }
                 */
            ]
        }],
    initComponent: function() {
        this.items[0].title =
                // '<div class="x-box-inner" style="padding-left: 20px; padding-right: 20px; height: 60px; background-color: #D5EAF3">'+
                '<div class="x-box-inner" style="padding-left: 10px;  padding-top:3px; padding-bottom:3px; padding-right: 10px; height: 42px">' +
                '<img class="logo" src="js/app/images/logo.png" height="36px" alt="Komunikator" border="0" align="left">' +
                '<p align="right"><a href="#" onclick="app.logout(); return false">' + app.msg.logout + '</a></p>' +
                '<p align="right">' + app.msg.user + ': ' + this.user_name + '</p>' +
                // '<p align="right"><a target="_blank" href="http://ats.digt.local/bugtracker/">BUG TRACKER</a></p>'+
                '</div>';

        this.callParent(arguments);

        Ext.TaskManager.start({
            run: function() {
                Ext.StoreMgr.each(function(item, index, length) {
                    if (item.storeId == 'statistic') {
                        if (item.autorefresh)
                            item.load();
                        // console.log(item.storeId + ":item.autorefresh-:" + item.autorefresh);
                    }
                    ;
                    if (Ext.getCmp(item.storeId + '_grid'))
                        if (app.active_store == item.storeId && item.autorefresh && !this.dirtyMark)
                            item.load();
                });
            },
            interval: app.refreshTime
        });
    }
});
