// google sheets
define(['jquery', 'underscore', 'twigjs', 'lib/components/base/modal'], function($, _, Twig, Modal) {
    var CustomWidget_WidgetGoogle = function() {
        var self = this,
            system = self.system,
            url_link_t = 'https://integratorgroup.k-on.ru/andreev/google_sheets/templates.php';

        // настройки прав доступа
        this.config_settings = {};

        // получение настроек
        this.getConfigSettings = function () {
            var config_settings = self.get_settings().config_settings || {};
            if (typeof config_settings !== 'string') config_settings = JSON.stringify(config_settings);
            config_settings = JSON.parse(config_settings);
            self.config_settings = config_settings;
        }

        // сохранение настроек
        this.saveConfigSettings = function () {
            $(`#${ self.get_settings().widget_code }_custom`).val(JSON.stringify(self.config_settings));
            $(`#${ self.get_settings().widget_code }_custom`).trigger('change');
        }

        /* ###################################################################### */

        // показываем настройки
        this.showSettingsJSON = function () {
            $.ajax({
                url: url_link_t,
                method: 'post',
                data: {
                    'domain': document.domain,
                    'method': 'showSettingsJSON'
                },
                dataType: 'json',
                success: function(settings) {
                    // console.log(settings);

                    // модалка обязательности полей
                    link_settings = `
                            <div class="widget_settings_block__item_field" style="margin-top: 10px;">
                                <div class="widget_settings_block__title_field" title="">
                                    <a href="" style="color: #979797; color: #4c8bf7;" class="help_link">
                                        Справка по полям обязательности
                                    </a>
                                </div>
                            </div>
                        `;
                    $('.widget_settings_block__controls').before(link_settings);

                    $('.help_link').unbind();
                    $('.help_link').bind('click', function (e) {
                        e.preventDefault();

                        new Modal({
                            class_name: 'modal_help',
                            init: function ($modal_body) {
                                var $this = $(this);
                                $modal_body
                                    .trigger('modal:loaded')
                                    .html(`
                                        <div class="modal_help_info" style="height: 350px;">
                                            <h1 class="modal-body__caption head_2">Правила именования</h1>
                                            Первый столбец во всех листах - Имя клиента </br></br>
                                            Все листы содержат столбцы: </br>
                                                - Где общаемся </br>
                                                - Телефон </br>
                                                - Бюджет </br>          
                                                - Менеджер </br>                                      
                                                - Фактический адрес </br>
                                                - ID сделки </br>
                                                - Ссылка на сделку </br></br>
                                            Листы "Подбор" и "Ожидают отправку" содержат также столбец: </br>
                                                - Смена воронки и статуса </br></br>
                                            Листы контейнеров содержат также столбец: </br>
                                                - Смена статуса всех сделок в листе                                            
                                        </div>
                                    `)
                                    .trigger('modal:centrify')
                                    .append('');
                            },
                            destroy: function () {}
                        });

                        // кнопка Закрыть
                        $('.modal_help').css('position', 'relative');
                        var cancelBtn = `
                            <a href="#" class="modal__cancelBtn__help" style="
                                text-decoration: none;
                                color: #92989b;
                                font-size: 14px;
                                font-weight: bold;
                                top: 34px;
                                right: 33px;
                                position: absolute;
                            ">Закрыть</a>
                        `;
                        $('.modal_help_info').append(cancelBtn);
                        $('.modal__cancelBtn__help').bind('click', function (e) {
                            e.preventDefault();
                            $('.modal_help').remove();
                        });
                    });

                    /* ##################################################################### */

                    var select_leads, select_contacts, title, select,
                        fields_leads = [],
                        fields_contacts = [];

                    // поля сделок
                    fields_leads.push({ option: 'Выберите поле' });
                    if (settings.fields_leads) {
                        // переворачиваем для упорядочивания сначала последних добавленных полей
                        settings.fields_leads = settings.fields_leads.reverse();

                        $.each(settings.fields_leads, function () {
                            fields_leads.push({ 'id': this[0], 'option': this[1] });
                        });
                    }

                    select_leads = Twig({ ref: '/tmpl/controls/select.twig' }).render({
                        items: fields_leads,
                        class_name: 'select__fields'
                    });

                    // поля контактов
                    fields_contacts.push({ option: 'Выберите поле' });
                    if (settings.fields_contacts) {
                        // переворачиваем для упорядочивания сначала последних добавленных полей
                        settings.fields_contacts = settings.fields_contacts.reverse();

                        $.each(settings.fields_contacts, function () {
                            fields_contacts.push({ 'id': this[0], 'option': this[1] });
                        });
                    }

                    select_contacts = Twig({ ref: '/tmpl/controls/select.twig' }).render({
                        items: fields_contacts,
                        class_name: 'select__fields'
                    });

                    /* ##################################################################### */

                    // столбцы листа Подбор
                    if (settings.selection && settings.fields_leads && settings.fields_contacts) {
                        select_settings = `
                            <div class="widget_settings_block__item_field" style="margin-top: 10px;">
                                <div class="widget_settings_block__title_field" title="" style="
                                    margin-bottom: 5px; font-weight: bold;">
                                    Настройка листа "Подбор":
                                </div>
                                <div class="for_selection"></div>
                            </div>
                        `;
                        $('.widget_settings_block__controls').before(select_settings);

                        $.each(settings.selection, function (key, value) {
                            // убираем лишние пробелы
                            title = value.toString().replace(/\s+/g, ' ');
                            // пропускаем ненастраиваемые поля
                            if (key === 0) title = 'Клиент';
                            if (title.toLowerCase() === 'где общаемся') return;
                            if (title.toLowerCase() === 'телефон') return;
                            if (title.toLowerCase() === 'бюджет') return;
                            if (title.toLowerCase() === 'менеджер') return;
                            if (title.toLowerCase() === 'id сделки') return;
                            if (title.toLowerCase() === 'ссылка на сделку') return;
                            if (title.toLowerCase() === 'смена воронки и статуса') return;

                            // для контактов и сделок поля разные
                            if (title.toLowerCase() === 'клиент' || title.toLowerCase() === 'фактический адрес') {
                                select = select_contacts;
                            } else select = select_leads;

                            $('.for_selection').before(`
                                <div class="widget_settings_block__input_field selection__wrapper" style="
                                    width: 100%; display: flex; flex-direction: row; margin-bottom: 3px;
                                    border-top: 1px solid #dbdedf; border-bottom: 1px solid #dbdedf;">
                                    <div class="selection_title" style="
                                        display: flex; align-items: center; width: 40%; padding-right: 3px;">
                                        ${ title }
                                    </div>
                                    <div class="selection__code" style="
                                        display: flex; align-items: center; width: 60%;">
                                        ${ select }
                                    </div>
                                </div>
                            `);
                        });

                        // показываем ранее сохраненные настройки
                        if (settings.selection_JSON) {
                            $.each(settings.selection_JSON, function () {

                                $.each(this, function (title, code) {
                                    $.each($('.selection_title'), function () {

                                        if ($(this).text().trim() === title) {
                                            $.each($(this).next().find('.control--select--list--item'), function () {
                                                if (parseInt($(this).attr('data-value')) === parseInt(code)) {
                                                    $(this).addClass('control--select--list--item-selected');
                                                    title = $(this).find('span').text();
                                                } else $(this).removeClass('control--select--list--item-selected');
                                            });

                                            $(this).next().find('.control--select--button').attr('data-value', code);
                                            $(this).next().find('.control--select--button-inner').text(title);
                                        }

                                    });
                                });
                            });
                        }
                    }

                    /* ##################################################################### */

                    // столбцы листа Ожидают отправки
                    if (settings.expect && settings.fields_leads && settings.fields_contacts) {
                        select_settings = `
                            <div class="widget_settings_block__item_field" style="margin-top: 10px;">
                                <div class="widget_settings_block__title_field" title="" style="
                                    margin-bottom: 5px; font-weight: bold;">
                                    Настройка листа "Ожидают отправку":
                                </div>
                                <div class="for_expect"></div>
                            </div>
                        `;
                        $('.widget_settings_block__controls').before(select_settings);

                        $.each(settings.expect, function (key, value) {
                            // убираем лишние пробелы
                            title = value.toString().replace(/\s+/g, ' ');
                            // пропускаем ненастраиваемые поля
                            if (key === 0) title = 'Клиент';
                            if (title.toLowerCase() === 'где общаемся') return;
                            if (title.toLowerCase() === 'телефон') return;
                            if (title.toLowerCase() === 'бюджет') return;
                            if (title.toLowerCase() === 'менеджер') return;
                            if (title.toLowerCase() === 'id сделки') return;
                            if (title.toLowerCase() === 'ссылка на сделку') return;
                            if (title.toLowerCase() === 'смена воронки и статуса') return;

                            // для контактов и сделок поля разные
                            if (title.toLowerCase() === 'клиент' || title.toLowerCase() === 'фактический адрес') {
                                select = select_contacts;
                            } else select = select_leads;

                            $('.for_expect').before(`
                                <div class="widget_settings_block__input_field expect__wrapper" style="
                                    width: 100%; display: flex; flex-direction: row; margin-bottom: 3px;
                                    border-top: 1px solid #dbdedf; border-bottom: 1px solid #dbdedf;">
                                    <div class="expect_title" style="
                                        display: flex; align-items: center; width: 40%; padding-right: 3px;">
                                        ${ title }
                                    </div>
                                    <div class="expect__code" style="
                                        display: flex; align-items: center; width: 60%;">
                                        ${ select }
                                    </div>
                                </div>
                            `);
                        });

                        // показываем ранее сохраненные настройки
                        if (settings.expect_JSON) {
                            $.each(settings.expect_JSON, function () {

                                $.each(this, function (title, code) {
                                    $.each($('.expect_title'), function () {

                                        if ($(this).text().trim() === title) {
                                            $.each($(this).next().find('.control--select--list--item'), function () {
                                                if (parseInt($(this).attr('data-value')) === parseInt(code)) {
                                                    $(this).addClass('control--select--list--item-selected');
                                                    title = $(this).find('span').text();
                                                } else $(this).removeClass('control--select--list--item-selected');
                                            });

                                            $(this).next().find('.control--select--button').attr('data-value', code);
                                            $(this).next().find('.control--select--button-inner').text(title);
                                        }

                                    });
                                });
                            });
                        }
                    }

                    /* ##################################################################### */

                    // столбцы листов контейнеров
                    if (settings.expect && settings.fields_leads && settings.fields_contacts) {
                        select_settings = `
                            <div class="widget_settings_block__item_field" style="margin-top: 10px;">
                                <div class="widget_settings_block__title_field" title="" style="
                                    margin-bottom: 5px; font-weight: bold;">
                                    Настройка листов контейнеров:
                                </div>
                                <div class="for_container"></div>
                            </div>
                        `;
                        $('.widget_settings_block__controls').before(select_settings);

                        $.each(settings.container, function (key, value) {
                            // убираем лишние пробелы
                            title = value.toString().replace(/\s+/g, ' ');
                            // пропускаем ненастраиваемые поля
                            if (key === 0) title = 'Клиент';
                            if (title.toLowerCase() === 'где общаемся') return;
                            if (title.toLowerCase() === 'телефон') return;
                            if (title.toLowerCase() === 'бюджет') return;
                            if (title.toLowerCase() === 'менеджер') return;
                            if (title.toLowerCase() === 'id сделки') return;
                            if (title.toLowerCase() === 'ссылка на сделку') return;
                            if (title.toLowerCase() === 'смена статуса всех сделок в листе') return;

                            // для контактов и сделок поля разные
                            if (title.toLowerCase() === 'клиент' || title.toLowerCase() === 'фактический адрес') {
                                select = select_contacts;
                            } else select = select_leads;

                            $('.for_container').before(`
                                <div class="widget_settings_block__input_field container__wrapper" style="
                                    width: 100%; display: flex; flex-direction: row; margin-bottom: 3px;
                                    border-top: 1px solid #dbdedf; border-bottom: 1px solid #dbdedf;">
                                    <div class="container_title" style="
                                        display: flex; align-items: center; width: 40%; padding-right: 3px;">
                                        ${ title }
                                    </div>
                                    <div class="container__code" style="
                                        display: flex; align-items: center; width: 60%;">
                                        ${ select }
                                    </div>
                                </div>
                            `);
                        });

                        // показываем ранее сохраненные настройки
                        if (settings.container_JSON) {
                            $.each(settings.container_JSON, function () {

                                $.each(this, function (title, code) {
                                    $.each($('.container_title'), function () {

                                        if ($(this).text().trim() === title) {
                                            $.each($(this).next().find('.control--select--list--item'), function () {
                                                if (parseInt($(this).attr('data-value')) === parseInt(code)) {
                                                    $(this).addClass('control--select--list--item-selected');
                                                    title = $(this).find('span').text();
                                                } else $(this).removeClass('control--select--list--item-selected');
                                            });

                                            $(this).next().find('.control--select--button').attr('data-value', code);
                                            $(this).next().find('.control--select--button-inner').text(title);
                                        }

                                    });
                                });
                            });
                        }
                    }

                    if ($('.select__fields').length) $('.select__fields').css('width', '100%');
                }
            });

            // $.ajax({
            //     url: url_link_t,
            //     method: 'post',
            //     data: {
            //         'domain': document.domain,
            //         'method': 'testing'
            //     },
            //     dataType: 'json',
            //     success: function (settings) {
            //         console.log(settings);
            //     }
            // });

        }

        /* ###################################################################### */

        // сохраняем настройки
        this.saveSettingsJSON = function () {
            var fields = {},
                selection = [],
                expect = [],
                container = [];

            // столбцы листа Подбор
            $.each($('.selection__code'), function () {
                selection_code = $(this).find('.control--select--button').attr('data-value');
                selection_title = $(this).prev().text().trim();

                if (selection_code !== 'Выберите поле') {
                    selection.push({
                        'title': selection_title,
                        'code': selection_code
                    });
                }
            });

            // столбцы листа Ожидают отправки
            $.each($('.expect__code'), function () {
                expect_code = $(this).find('.control--select--button').attr('data-value');
                expect_title = $(this).prev().text().trim();

                if (expect_code !== 'Выберите поле') {
                    expect.push({
                        'title': expect_title,
                        'code': expect_code
                    });
                }
            });

            // столбцы листов контейнеров
            $.each($('.container__code'), function () {
                var container_code = $(this).find('.control--select--button').attr('data-value');
                var container_title = $(this).prev().text().trim();

                if (container_code !== 'Выберите поле') {
                    container.push({
                        'title': container_title,
                        'code': container_code
                    });
                }
            });

            fields.selection = selection;
            fields.expect = expect;
            fields.container = container;

            $.ajax({
                url: url_link_t,
                method: 'post',
                data: {
                    'domain': document.domain,
                    'method': 'saveSettingsJSON',
                    'fields': fields
                },
                dataType: 'json',
                success: function(settings) {}
            });
        }

        /* ###################################################################### */

        // сохраняем ссылку ВК в БД
        this.saveLinkVK = function () {
            var contact_ID = null,
                link = null;

            if (!$('.linked-form__field .profile_messengers-item-vk').length) return;
            contact_ID = $('.linked-form__field .profile_messengers-item-vk').attr('data-entity');
            if (!contact_ID) return;

            if (!$('.linked-form__field .profile_messengers-item-vk .tips-item a').length) return;
            link = $('.linked-form__field .profile_messengers .tips-item a').attr('href');
            if (!link.length || !link.includes('vk.com')) return;

            $.ajax({
                url: url_link_t,
                method: 'post',
                data: {
                    'domain': document.domain,
                    'method': 'saveLinkVK',
                    'link': link,
                    'contact_ID': contact_ID
                },
                dataType: 'json',
                success: function(data) {}
            });
        }

        /* ###################################################################### */

        this.callbacks = {
            settings: function() {
                // загрузка настроек
                self.showSettingsJSON();

                // Блок первичных настроек и авторизации
                var _settings = self.get_settings();
                var data = '<div id="settings_WidgetGoogle">Загружается...</div>';
                $('[id="settings_WidgetGoogle"]').remove();
                $('#' + _settings.widget_code + '_custom_content').parent().after(data);
                var _secret = $('p.js-secret').attr('title');
                var _data = {};
                _data["domain"] = document.domain;
                _data["settings"] = _settings;
                _data["secret"] = _secret;
                _data["method"] = "settings";
                $.ajax({
                    url: url_link_t,
                    method: 'post',
                    data: _data,
                    dataType: 'html',
                    success: function(data) {
                        $('[id="settings_WidgetGoogle"]').remove();
                        $('#' + _settings.widget_code + '_custom_content').parent().after(data);
                    }
                });
            },
            init: function() {
                return true;
            },
            bind_actions: function() {
                return true;
            },
            render: function() {
                // сохраняем ссылку ВК в БД
                if (AMOCRM.getBaseEntity() === 'leads' && AMOCRM.isCard() === true) self.saveLinkVK();
                return true;
            },
            contacts: {
                selected: function () {}
            },
            companies: {
                selected: function () {},
            },
            leads: {
                selected: function () {}
            },
            tasks: {
                selected: function() {}
            },
            destroy: function() {
                // delete install.widget
                $.ajax({
                    url: url_link_t,
                    method: 'post',
                    data: {
                        'domain': document.domain,
                        'method': 'widget_status',
                        'status': 'destroy'
                    },
                    dataType: 'json',
                    success: function(data) {}
                });
            },
            onSave: function() {
                // сохранение настроек
                self.saveSettingsJSON();

                // create install.widget
                $.ajax({
                    url: url_link_t,
                    method: 'post',
                    data: {
                        'domain': document.domain,
                        'method': 'widget_status',
                        'status': 'install'
                    },
                    dataType: 'json',
                    success: function(data) {}
                });

                var _settings = self.get_settings();
                var data = '<div id="settings_WidgetGoogle">Загружается...</div>';
                $('[id="settings_WidgetGoogle"]').remove();
                $('#' + _settings.widget_code + '_custom_content').parent().after(data);
                var _secret = $('p.js-secret').attr('title');
                var _data = {};
                _data["domain"] = document.domain;
                _data["settings"] = _settings;
                _data["settings"]["active"] = "Y";
                _data["secret"] = _secret;
                _data["method"] = "settings";
                $.ajax({
                    url: url_link_t,
                    method: 'post',
                    data: _data,
                    dataType: 'html',
                    success: function(data) {
                        $('[id="settings_WidgetGoogle"]').remove();
                        $('#' + _settings.widget_code + '_custom_content').parent().after(data);
                    }
                });

                return true;
            },
            advancedSettings: function() {}
        };
        return this;
    };
    return CustomWidget_WidgetGoogle;
});

// https://integratorgroup.k-on.ru/andreev/google_sheets/token_get.php