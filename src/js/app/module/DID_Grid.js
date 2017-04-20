/*
 *  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 
 *    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
 *    Copyright (C) 2012-2017, ООО «Телефонные системы»
 
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
 *    Copyright (C) 2012-2017, "Telephonnyie sistemy" Ltd.
 
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
    fds: function(val, field) {
        if (val !== app.msg.attendant) {
            console.log(field.ownerCt.items.items[4].setVisible(false));
            console.log(field.ownerCt.items.items[4].setValue(null));
            return true;
        }

        console.log(field.ownerCt.items.items[4].setVisible(true));
        return true;
    }
});

Ext.define('app.module.DID_Grid', {
    extend: 'app.Grid',
    store_cfg: {
        fields: ['id', 'number', 'destination', 'description', 'default_dest'],
        storeId: 'dids'
    },
    // advanced : ['description'],

    columns: [
        {// 'id'
            hidden: true
        },
        {// 'number'
            width: 150,
            editor: {
                xtype: 'textfield',
                regex: /^.+$/,
                allowBlank: false
            }
        },
        {// 'destination'
            width: 150,
            editor: app.get_Source_Combo({
                allowBlank: false,
                editable: false,
                vtype: 'fds'
            })
        },
        {// 'description'
            width: 150,
            editor: {
                xtype: 'textfield'
            }
        },
        {// 'default_dest'
            width: 150,
            // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
            // было создано отдельное хранилище sources_exception
            // в котором отсутствуют: Автосекретарь, Голосовая почта

            editor: {
                xtype: 'combobox',
                store: Ext.StoreMgr.lookup('sources_exception') ?
                        Ext.StoreMgr.lookup('sources_exception') :
                        Ext.create('app.Store', {
                    fields: ['id', 'name'],
                    storeId: 'sources_exception'
                }),
                queryMode: 'local',
                displayField: 'name',
                valueField: 'name',
                editable: false,
              
            }
            // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        }
    ],
    columns_renderer:
            function(value, metaData, record, rowIndex, colIndex, store) {
                if (colIndex == 2 && app.msg[value]) {
                    return app.msg[value];
                }
                return value;
            },
    initComponent: function() {
        this.callParent(arguments);
        this.store.on('load',
                function(store, records, success) {

                    var grid = Ext.getCmp(this.storeId + '_grid');  // поиск объекта по ID
                    if (grid && !this.autoLoad)
                        grid.ownerCt.body.unmask();     // «серый» экран – блокировка действий пользователя
                    this.Total_sync();                  // количество записей
                    this.dirtyMark = false;             // измененных записей нет
                    if (!success && store.storeId) {
                        store.removeAll();
                        if (store.autorefresh != undefined)
                            store.autorefresh = false;
                        console.log('ERROR: ' + store.storeId + ' fail_load [code of Call_website_Grid.js]');
                    }

                }
        );
    }
});
