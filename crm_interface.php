<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<div class="logistic-container">

    <!-- Верхняя панель управления и навигации -->
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <a href="?view_archive=N" class="btn-logistic <?=((!$showSuppliersTab && $showArchive=='N')?'btn-primary':'btn-secondary')?>">📋 Текущие заявки</a>
            <a href="?view_archive=Y" class="btn-logistic <?=((!$showSuppliersTab && $showArchive=='Y')?'btn-primary':'btn-secondary')?>">📦 Архив заявок</a>
            <a href="?view_suppliers=Y" class="btn-logistic <?=($showSuppliersTab?'btn-primary':'btn-secondary')?>">👥 База поставщиков (<?=count($allSuppliers)?>)</a>
        </div>
        <button id="openModalBtn" class="btn-logistic btn-success" style="font-size: 14px; padding: 9px 20px; font-weight: bold; cursor: pointer;">➕ Создать доставку</button>
    </div>

    <!-- МОДАЛЬНОЕ ОКНО СОЗДАНИЯ ДОСТАВКИ (AJAX) -->
    <div id="deliveryModal" class="logistic-modal">
        <div class="logistic-modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:12px; margin-bottom:20px;">
                <h3 style="margin:0; color:#007bff; font-size:18px;">📦 Новая логистическая заявка</h3>
                <span id="closeModalBtn" style="color:#aaa; font-size:28px; font-weight:bold; cursor:pointer; line-height:20px;">&times;</span>
            </div>
            
            <form id="ajaxOrderForm" class="logistic-form" style="background:transparent; padding:0; border:none; box-shadow:none; margin:0;">
                <div class="logistic-form-row">
                    <!-- Блок Отправителя -->
                    <div class="logistic-form-col-6 col-supplier">
                        <h4>🏢 Отправитель / Поставщик</h4>
                        <label>Дата заявки:*</label>
                        <input type="date" name="date" required value="<?=date('Y-m-d')?>">
                        
                        <label>№ Счета:*</label>
                        <input type="text" name="number" required placeholder="Например, АВ-154">
                        
                        <label>Компания-поставщик:*</label>
                        <select id="modalSupplierSelect" name="order_supplier_id" required style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #ced4da; border-radius: 4px;">
                            <option value="">-- Выбрать из базы справочника --</option>
                            <?foreach($allSuppliers as $sup):?>
                                <option value="<?=$sup['id']?>" data-name="<?=htmlspecialcharsbx($sup['name'])?>"><?=htmlspecialcharsbx($sup['name'])?></option>
                            <?endforeach;?>
                            <option value="MANUAL">-- Ввести вручную (Новый контрагент) --</option>
                        </select>
                        
                        <div id="manualSupplierBlock" style="display:none;">
                            <label>Название новой компании:</label>
                            <input type="text" id="modal_company_supplier" name="company_supplier" placeholder="Введите название юр. лица">
                        </div>
                        
                        <label>Адрес склада загрузки:</label>
                        <input type="text" id="modal_supplier_address" name="supplier_address" placeholder="г. Москва, ул. Ленина 1">
                        
                        <label>Телефон:</label>
                        <input type="text" id="modal_supplier_phone" name="supplier_phone" placeholder="+7...">
                        
                        <label>Комментарий к загрузке:</label>
                        <textarea id="modal_supplier_comment" name="supplier_comment" placeholder="Ворота №3"></textarea>
                    </div>
                    
                    <!-- Блок Получателя -->
                    <div class="logistic-form-col-6 col-client">
                        <h4>🏪 Получатель / Клиент</h4>
                        <label>Компания-клиент:</label>
                        <input type="text" name="company_client" placeholder="ООО Клиент">
                        
                        <label>Адрес выгрузки:</label>
                        <input type="text" name="client_address" placeholder="г. Спб, ул. Новая 5">
                        
                        <label>Телефон клиента:</label>
                        <input type="text" name="client_phone" placeholder="+7...">
                        
                        <label>Комментарий к выгрузке:</label>
                        <textarea name="client_comment" placeholder="Разгрузка до 18:00"></textarea>
                    </div>
                </div>
                <div style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" id="cancelModalBtn" class="btn-logistic btn-secondary" style="margin-right:10px;">Отмена</button>
                    <button type="submit" class="btn-logistic btn-success">Создать заявку</button>
                </div>
            </form>
        </div>
    </div>

    <?if($showSuppliersTab):?>
        <!-- РАЗДЕЛ СПРАВОЧНИКА ПОСТАВЩИКОВ -->
        <div class="logistic-form">
            <h3><?=($editSupplier ? '✏️ Редактировать карточку поставщика' : '➕ Добавить поставщика в справочник')?></h3>
            <form method="POST">
                <input type="hidden" name="supplier_action" value="<?=($editSupplier ? 'edit' : 'add')?>">
                <?if($editSupplier):?><input type="hidden" name="s_id" value="<?=$editSupplier['id']?>"><?endif;?>
                <div class="logistic-form-row">
                    <div class="logistic-form-col-6 col-supplier">
                        <label>Название юр. лица:</label>
                        <input type="text" name="s_name" value="<?=($editSupplier ? htmlspecialcharsbx($editSupplier['name']) : '')?>" required placeholder="ООО МеталлТрейд">
                        <label>Точный адрес склада:</label>
                        <input type="text" name="s_address" value="<?=($editSupplier ? htmlspecialcharsbx($editSupplier['address']) : '')?>" required placeholder="г. Москва, ул. Заводская, д. 2">
                        <label>ФИО Менеджера:</label>
                        <input type="text" name="s_manager" value="<?=($editSupplier ? htmlspecialcharsbx($editSupplier['manager']) : '')?>" placeholder="Иванов Александр">
                    </div>
                    <div class="logistic-form-col-6 col-client">
                        <label>Телефон:</label>
                        <input type="text" name="s_phone" class="js-phone-input" value="<?=($editSupplier ? htmlspecialcharsbx($editSupplier['phone']) : '+7 ')?>">
                        <label>E-mail:</label>
                        <input type="email" name="s_email" value="<?=($editSupplier ? htmlspecialcharsbx($editSupplier['email']) : '')?>" placeholder="manager@supplier.ru">
                        <label>Постоянная заметка:</label>
                        <textarea name="s_comment" placeholder="Въезд под шлагбаум"><?=($editSupplier ? htmlspecialcharsbx($editSupplier['comment']) : '')?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-logistic btn-success"><?=($editSupplier ? 'Сохранить изменения' : 'Внести в базу контрагентов')?></button>
                <?if($editSupplier):?><a href="?view_suppliers=Y" class="btn-logistic btn-secondary" style="margin-left:10px;">Отмена</a><?endif;?>
            </form>
        </div>

        <div class="logistic-search-box">
            <input type="text" id="skladSearch" class="logistic-search-input" placeholder="🔍 Быстрый поиск в справочнике...">
        </div>

        <table class="logistic-table">
            <thead>
                <tr><th>Поставщик</th><th>Адрес склада</th><th>Менеджер</th><th>Контакты</th><th>Заметка</th><th style="width:75px; text-align:center;">Действия</th></tr>
            </thead>
            <tbody id="logisticTableBody">
                <?foreach($allSuppliers as $sup):?>
                    <tr class="logistic-tr-item">
                        <td><strong style="font-size:15px; color:#007bff;"><?=htmlspecialcharsbx($sup['name'])?></strong></td>
                        <td><?=htmlspecialcharsbx($sup['address'])?></td>
                        <td><?=htmlspecialcharsbx($sup['manager'])?></td>
                        <td><?=htmlspecialcharsbx($sup['phone'])?><br><small><?=htmlspecialcharsbx($sup['email'])?></small></td>
                        <td><?=htmlspecialcharsbx($sup['comment'])?></td>
                        <td>
                            <div class="action-btn-group" style="display:flex; gap:6px; justify-content:center; align-items:center;">
                                <a href="?view_suppliers=Y&edit_supplier_id=<?=$sup['id']?>" class="btn-action-minimal btn-warn" title="Правка" style="display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; background:#f59e0b; color:#fff; border-radius:4px; text-decoration:none; font-size:11px;">✏️</a>
                                <a href="?view_suppliers=Y&delete_supplier_id=<?=$sup['id']?>" class="btn-action-minimal btn-crit" title="Удалить" onclick="return confirm('Удалить поставщика из базы?')" style="display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; background:#ef4444; color:#fff; border-radius:4px; text-decoration:none; font-size:11px;">❌</a>
                            </div>
                        </td>
                    </tr>
                <?endforeach;?>
            </tbody>
        </table>
    <?else:?>
        <!-- БЛОК 2: ЖУРНАЛ СЧЕТОВ С ИНТЕЛЛЕКТУАЛЬНОЙ ГРУППИРОВКОЙ ПО ДНЯМ -->
        <div class="logistic-search-box">
            <input type="text" id="orderSearch" class="logistic-search-input" placeholder="🔍 Быстрый поиск (номер, фирма, адрес)...">
        </div>

        <!-- ОПТИМИЗИРОВАННЫЕ КОМПАКТНЫЕ СТИЛИ ДЕЙСТВИЙ -->
        <style>
            .modern-action-bar { display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 4px; border-radius: 4px; }
            .btn-modern-act { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 4px; border: none; text-decoration: none !important; font-size: 11px; cursor: pointer; transition: background 0.15s ease, color 0.15s ease; color: #fff !important; }
            .sort-tower { display: flex; flex-direction: column; gap: 1px; }
            .btn-tower-sort { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 11px; font-size: 8px; background: #64748b; color: #fff !important; text-decoration: none; border-radius: 2px; line-height: 11px; }
            .btn-tower-sort:hover { background: #475569; }
            .btn-modern-edit { background: #3b82f6; }
            .btn-modern-edit:hover { background: #2563eb; }
            .btn-modern-box { background: #f59e0b; }
            .btn-modern-box:hover { background: #d97706; }
            .btn-modern-del { background: #ef4444; }
            .btn-modern-del:hover { background: #dc2626; }
            .logistic-table td { vertical-align: middle !important; padding: 8px 10px !important; }
        </style>

        <?if(empty($orders)):?>
            <div class="logistic-form" style="text-align:center; padding:30px; color:#666;">Счетов пока нет</div>
        <?else:?>
            <?
            $groupedOrders = [];
            foreach($orders as $order) {
                $dateKey = !empty($order['order_date']) ? date('d.m.Y', strtotime($order['order_date'])) : 'Дата не указана';
                $groupedOrders[$dateKey][] = $order;
            }

            $yandexDomain = "api-maps." . "yandex.ru";
            $yandexVersion = "2.1";
            $yandexKey = "d2dfaf47-545d-4840-99b7-462fa04e9113";
            $yandexLang = "ru_RU";
            $finalApiUrl = "https://" . $yandexDomain . "/" . $yandexVersion . "/?apikey=" . $yandexKey . "&lang=" . $yandexLang;
            ?>

            <script src="<?=$finalApiUrl?>" type="text/javascript"></script>

            <? $dayIndex = 0; ?>
            <?foreach($groupedOrders as $dateBlock => $dayOrders):?>
                <? $dayIndex++; ?>
                <div class="logistic-day-group" style="margin-bottom: 35px;">
                    
                    <div class="logistic-day-header" style="background: #334155; color: #fff; padding: 10px 15px; font-weight: bold; font-size: 15px; border-radius: 4px 4px 0 0; display: flex; justify-content: space-between; align-items: center;">
                        <span>📅 Счета на день: <?=$dateBlock?></span>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?if($showArchive == 'N'):?>
                                <button type="button" class="btn-logistic js-toggle-map-btn" data-target="mapWrapper_<?=$dayIndex?>" style="font-size: 12px; padding: 4px 12px; background: #1a73e8; color: #fff !important; border: none; cursor: pointer; border-radius: 4px;">🗺️ Карта маршрута</button>
                            <?endif;?>
                            <a href="?print_day=<?=date('Y-m-d', strtotime($dateBlock))?>" target="_blank" class="btn-logistic btn-success" style="font-size: 12px; padding: 4px 12px; background: #28a745; color: #fff !important; text-decoration: none; border-radius: 4px;">🖨️ Печать дня</a>
                            <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; font-size: 12px;">Всего: <?=count($dayOrders)?></span>
                        </div>
                    </div>

                    <table class="logistic-table" style="margin-top: 0; border-top: none; border-radius: 0; margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th class="col-num" style="width: 90px;">№ Счет</th>
                                <th class="col-addr">Поставщик (Склад загрузки)</th>
                                <th class="col-addr">Клиент (Точка выгрузки)</th>
                                <th class="col-act" style="text-align:center !important; width: 135px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="ordersTableDayBody">
                            <?foreach($dayOrders as $order):?>
                                <tr class="logistic-tr-item" data-id="<?=$order['id']?>">
                                    <td><strong class="edit-inline" data-field="order_number"><?=htmlspecialcharsbx($order['order_number'])?></strong></td>
                                    <td>
                                        <strong class="edit-inline" data-field="company_supplier"><?=htmlspecialcharsbx($order['company_supplier'])?></strong><br>
                                        <span class="edit-inline" data-field="supplier_address"><?=htmlspecialcharsbx($order['supplier_address'])?></span><br>
                                        <small class="edit-inline text-muted" data-field="supplier_phone" style="color:#666;"><?=htmlspecialcharsbx($order['supplier_phone'])?></small><br>
                                        <span class="edit-inline comment-text" data-field="supplier_comment" style="display:block; font-style:italic; color:#555;"><?=htmlspecialcharsbx($order['supplier_comment'])?></span>
                                    </td>
                                    <td>
                                        <strong class="edit-inline" data-field="company_client"><?=htmlspecialcharsbx($order['company_client'])?></strong><br>
                                        <span class="edit-inline" data-field="client_address"><?=htmlspecialcharsbx($order['client_address'])?></span><br>
                                        <small class="edit-inline text-muted" data-field="client_phone" style="color:#666;"><?=htmlspecialcharsbx($order['client_phone'])?></small><br>
                                        <span class="edit-inline comment-text" data-field="client_comment" style="display:block; font-style:italic; color:#555;"><?=htmlspecialcharsbx($order['client_comment'])?></span>
                                    </td>
                                    <td style="text-align: center !important;">
                                        <div class="modern-action-bar">
                                            <!-- СОРТИРОВКА В ДВА ЭТАЖА -->
                                            <div class="sort-tower">
                                                <a href="?move_id=<?=$order['id']?>&dir=up&view_archive=<?=$showArchive?>" class="btn-tower-sort" title="Вверх">▲</a>
                                                <a href="?move_id=<?=$order['id']?>&dir=down&view_archive=<?=$showArchive?>" class="btn-tower-sort" title="Вниз">▼</a>
                                            </div>
                                            
                                            <!-- КНОПКА РЕДАКТИРОВАНИЯ И СЛУЖЕБНЫЕ КНОПКИ -->
                                            <button type="button" class="btn-modern-act btn-modern-edit js-trigger-inline-edit" title="Редактировать">✏️</button>
                                            <?if($showArchive == 'N'):?>
                                                <a href="?archive_id=<?=$order['id']?>" class="btn-modern-act btn-modern-box" title="В архив">📦</a>
                                            <?else:?>
                                                <a href="?unarchive_id=<?=$order['id']?>" class="btn-modern-act btn-modern-box" title="Вернуть из архива">🔄</a>
                                            <?endif;?>
                                            <a href="?delete_id=<?=$order['id']?>" class="btn-modern-act btn-modern-del" title="Удалить" onclick="return confirm('Удалить счет безвозвратно?')">❌</a>
                                        </div>
                                    </td>
                                </tr>
                            <?endforeach;?>
                        </tbody>
                    </table>

                    <?if($showArchive == 'N'):?>
                        <div id="mapWrapper_<?=$dayIndex?>" style="display: none; width: 100%; border: 1px solid #ced4da; border-top: none; border-radius: 0 0 4px 4px; box-sizing: border-box;">
                            <div id="dayMap_<?=$dayIndex?>" style="width:100%; height:360px; background:#f8f9fa; position: relative; overflow: hidden;"></div>
                        </div>
                        
                        <script type="text/javascript">
                            ymaps.ready(function() {
                                const mapId = "dayMap_<?=$dayIndex?>";
                                const myMap = new ymaps.Map(mapId, {
                                    center: [55.76, 37.64],
                                    zoom: 9,
                                    controls: ['zoomControl', 'fullscreenControl']
                                });

                                const dayPoints = [];
                                <? foreach($dayOrders as $order): ?>
                                    <? if(!empty($order['supplier_address'])): ?>
                                        <? 
                                        $cleanAddr = trim($order['supplier_address']);
                                        $cleanAddr = str_replace(["\r", "\n", '"', "'", '`', '\\'], ' ', $cleanAddr);
                                        $cleanAddr = preg_replace('/\s+/', ' ', $cleanAddr);
                                        ?>
                                        dayPoints.push("<?=CUtil::JSEscape($cleanAddr)?>");
                                    <? endif; ?>
                                    <? if(!empty($order['client_address'])): ?>
                                        <? 
                                        $cleanAddr = trim($order['client_address']);
                                        $cleanAddr = str_replace(["\r", "\n", '"', "'", '`', '\\'], ' ', $cleanAddr);
                                        $cleanAddr = preg_replace('/\s+/', ' ', $cleanAddr);
                                        ?>
                                        dayPoints.push("<?=CUtil::JSEscape($cleanAddr)?>");
                                    <? endif; ?>
                                <? endforeach; ?>

                                if (dayPoints.length >= 2) {
                                    const geoCoordinates = [];

                                    function geocodePoint(index) {
                                        if (index >= dayPoints.length) {
                                            if (geoCoordinates.length >= 2) {
                                                const multiRoute = new ymaps.multiRouter.MultiRoute({
                                                    referencePoints: geoCoordinates,
                                                    params: { routingMode: 'auto' }
                                                }, {
                                                    boundsAutoApply: true,
                                                    wayPointVisible: false 
                                                });
                                                myMap.geoObjects.add(multiRoute);
                                                
                                                document.getElementById("mapWrapper_<?=$dayIndex?>").setAttribute("data-map-loaded", "Y");
                                                window["ymap_obj_" + "<?=$dayIndex?>"] = myMap;
                                            }
                                            return;
                                        }

                                        ymaps.geocode(dayPoints[index], { results: 1 }).then(function (res) {
                                            const firstGeoObject = res.geoObjects.get(0);
                                            if (firstGeoObject) {
                                                const coords = firstGeoObject.geometry.getCoordinates();
                                                geoCoordinates.push(coords);
                                                
                                                const placemark = new ymaps.Placemark(coords, {
                                                    iconContent: index + 1
                                                }, {
                                                    preset: 'islands#blackCircleIcon'
                                                });
                                                myMap.geoObjects.add(placemark);
                                            }
                                            geocodePoint(index + 1);
                                        }, function () {
                                            geocodePoint(index + 1);
                                        });
                                    }

                                    geocodePoint(0);
                                }
                            });
                        </script>
                    <?endif;?>
                </div>
            <?endforeach;?>
        <?endif;?>
    <?endif;?>
</div>
<!-- СКРИПТЫ АВТОМАТИЗАЦИИ ИНТЕРФЕЙСА CRM -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. УПРАВЛЕНИЕ МОДАЛЬНЫМ ОКНОМ СОЗДАНИЯ ДОСТАВКИ
    const modal = document.getElementById('deliveryModal');
    const openBtn = document.getElementById('openModalBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelModalBtn');
    const ajaxForm = document.getElementById('ajaxOrderForm');

    if (openBtn) {
        openBtn.addEventListener('click', function() {
            if (modal) modal.style.display = 'block';
        });
    }

    const closeModal = function() {
        if (modal) modal.style.display = 'none';
        if (ajaxForm) ajaxForm.reset();
        const manualBlock = document.getElementById('manualSupplierBlock');
        if (manualBlock) manualBlock.style.display = 'none';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    
    window.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    // 2. ИНТЕЛЛЕКТУАЛЬНЫЙ ТРИГГЕР ДЛЯ СЛУЖЕБНЫХ СПОЙЛЕРОВ КАРТ ДНЯ
    document.querySelectorAll('.js-toggle-map-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const wrapper = document.getElementById(targetId);
            if (!wrapper) return;

            if (wrapper.style.display === 'none' || wrapper.style.display === '') {
                wrapper.style.display = 'block';
                this.style.background = '#0f172a';
                
                const dayIdx = targetId.split('_')[1];
                const mapInstance = window["ymap_obj_" + dayIdx];
                
                if (mapInstance) {
                    setTimeout(() => {
                        mapInstance.container.fitToViewport();
                    }, 50);
                }
            } else {
                wrapper.style.display = 'none';
                this.style.background = '#1a73e8';
            }
        });
    });

    // 3. ИНТЕГРАЦИЯ АВТОЗАПОЛНЕНИЯ ПО СВЯЗКЕ ПОСТАВЩИКОВ
    const supplierSelect = document.getElementById('modalSupplierSelect');
    const manualBlock = document.getElementById('manualSupplierBlock');
    const manualInput = document.getElementById('modal_company_supplier');

    if (supplierSelect) {
        supplierSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            if (selectedValue === 'MANUAL') {
                if (manualBlock) manualBlock.style.display = 'block';
                if (manualInput) { manualInput.value = ''; manualInput.required = true; }
                document.getElementById('modal_supplier_address').value = '';
                document.getElementById('modal_supplier_phone').value = '';
                document.getElementById('modal_supplier_comment').value = '';
                return;
            }

            if (!selectedValue) {
                if (manualBlock) manualBlock.style.display = 'none';
                if (manualInput) { manualInput.value = ''; manualInput.required = false; }
                document.getElementById('modal_supplier_address').value = '';
                document.getElementById('modal_supplier_phone').value = '';
                document.getElementById('modal_supplier_comment').value = '';
                return;
            }

            if (manualBlock) manualBlock.style.display = 'none';
            if (manualInput) { manualInput.value = ''; manualInput.required = false; }

            fetch(`?ajax_action=get_supplier_info&supplier_id=${selectedValue}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modal_supplier_address').value = data.address || '';
                        document.getElementById('modal_supplier_phone').value = data.phone || '';
                        document.getElementById('modal_supplier_comment').value = data.comment || '';
                    } else {
                        alert('Ошибка подгрузки контрагента: ' + (data.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(() => alert('Сетевая ошибка при получении данных контрагента.'));
        });
    }

    // Фоновое создание заявки через AJAX
    if (ajaxForm) {
        ajaxForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax_action', 'create_delivery');

            if (supplierSelect && supplierSelect.value !== 'MANUAL' && supplierSelect.value !== '') {
                const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
                formData.append('company_supplier', selectedOption.text);
            }

            fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    window.location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось сохранить доставку'));
                }
            })
            .catch(() => alert('Сетевая ошибка при сохранении заявки.'));
        });
    }

    // 4. ИНЛАЙН-РЕДАКТИРОВАНИЕ ТЕКСТОВЫХ ПОЛЕЙ ТАБЛИЦЫ
    const startInlineEdit = function(element) {
        if (element.querySelector('input') || element.querySelector('textarea')) return;

        const originalText = element.innerText;
        const fieldName = element.getAttribute('data-field');
        const tr = element.closest('tr');
        const elementId = tr.getAttribute('data-id');
        
        let input;
        if (fieldName.includes('comment') || fieldName.includes('address')) {
            input = document.createElement('textarea');
            input.className = 'ajax-edit-textarea';
        } else {
            input = document.createElement('input');
            input.type = 'text';
            input.className = 'ajax-edit-input';
        }
        
        input.value = originalText;
        element.innerHTML = '';
        element.appendChild(input);
        input.focus();

        const saveChanges = () => {
            const newValue = input.value.trim();
            if (newValue === originalText) {
                element.innerText = originalText;
                return;
            }

            const formData = new FormData();
            formData.append('ajax_action', 'save_order_row');
            formData.append('element_id', elementId);
            
            tr.querySelectorAll('.edit-inline').forEach(el => {
                const f = el.getAttribute('data-field');
                if (f === fieldName) {
                    formData.append(f, newValue);
                } else {
                    const subInput = el.querySelector('input, textarea');
                    formData.append(f, subInput ? subInput.value : el.innerText);
                }
            });

            fetch('', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        element.innerText = newValue;
                        tr.style.backgroundColor = '#e8f5e9';
                        setTimeout(() => tr.style.backgroundColor = '', 500);
                    } else {
                        alert('Ошибка сохранения изменений');
                        element.innerText = originalText;
                    }
                })
                .catch(() => {
                    alert('Сетевая ошибка при изменении ячейки.');
                    element.innerText = originalText;
                });
        };

        input.addEventListener('blur', saveChanges);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                input.blur();
            }
            if (e.key === 'Escape') {
                element.innerText = originalText;
            }
        });
    };

    // Активация редактирования по клику на ячейку текста
    document.querySelectorAll('.edit-inline').forEach(element => {
        element.addEventListener('click', function() {
            startInlineEdit(this);
        });
    });

    // Активация редактирования при нажатии кнопки-карандаша в панели действий
    document.querySelectorAll('.js-trigger-inline-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            const numField = tr.querySelector('.edit-inline[data-field="order_number"]');
            if (numField) {
                startInlineEdit(numField);
            }
        });
    });

    // 5. КЛИЕНТСКИЙ МГНОВЕННЫЙ ПОИСК ПО ЗАЯВКАМ
    const searchOrder = document.getElementById('orderSearch');
    if (searchOrder) {
        searchOrder.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            
            document.querySelectorAll('.logistic-day-group').forEach(group => {
                let activeRowsInDay = 0;
                
                group.querySelectorAll('.ordersTableDayBody .logistic-tr-item').forEach(tr => {
                    const matches = tr.innerText.toLowerCase().includes(filter);
                    tr.style.display = matches ? '' : 'none';
                    if (matches) activeRowsInDay++;
                });
                
                group.style.display = (activeRowsInDay > 0 || filter === '') ? '' : 'none';
            });
        });
    }

    // 6. КЛИЕНТСКИЙ МГНОВЕННЫЙ ПОИСК ПО СПРАВОЧНИКУ ПОСТАВЩИКОВ
    const searchSklad = document.getElementById('skladSearch');
    if (searchSklad) {
        searchSklad.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('#logisticTableBody .logistic-tr-item').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>
