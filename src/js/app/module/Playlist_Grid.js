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

Ext.define('app.module.Playlist_Grid', {
    extend: 'app.Grid',
    load_after_edit: true,
    store_cfg: {
        fields: ['id', 'playlist', 'in_use'],
        storeId: 'playlists'
                // storeId : 'playlist_extended'
    },
    columns: [
        {//id
            hidden: true
        },
        {//playlist
            editor: {
                xtype: 'textfield',
                allowBlank: false
            }
        },
        {//in_use
            renderer: app.checked_render,
            editor: {
                xtype: 'checkbox',
                listeners: {
                    change: function(c, n, o) {
                        //var store = Ext.StoreMgr.lookup('playlists');
                        //	store.load();
                        /*Ext.each(store.getRange(),function(record){
                         record.set({
                         in_use:!n
                         });
                         console.log(record);
                         });
                         */
                    }
                },
                style: {
                    textAlign: 'center'
                }
            }
        }
    ],
    initComponent: function() {
        this.callParent(arguments);
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // при внесении изменений в хранилище playlist
        // повторная загрузка (обновление записей) хранилища playlist_extended
        this.store.on('load',
                function(store, records, success) {

                    var grid = Ext.getCmp(this.storeId + '_grid');  // поиск объекта по ID
                    if (grid && !this.autoLoad)
                        grid.ownerCt.body.unmask();  // «серый» экран – блокировка действий пользователя
                    this.Total_sync();  // количество записей
                    this.dirtyMark = false;  // измененных записей нет
                    if (!success && store.storeId) {
                        store.removeAll();
                        if (store.autorefresh != undefined)
                            store.autorefresh = false;
                        console.log('ERROR: ' + store.storeId + ' fail_load [code of Playlist_Grid.js]');
                    }


                    var repository_exists = Ext.StoreMgr.lookup('playlist_extended');

                    if (repository_exists)
                        repository_exists.load()
                    else
                        console.log('ERROR: playlist_extended - fail_load [code of Playlist_Grid.js]');
                }

        );
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    }
})