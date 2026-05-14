<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<div class="logistic-container">

    <!-- Панель управления: Навигация + Календарь -->
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <a href="?view_suppliers=N" class="btn-logistic <?=((!$showSuppliersTab)?'btn-primary':'btn-secondary')?>">Все текущие заявки</a>
            <a href="?view_suppliers=Y" class="btn-logistic <?=($showSuppliersTab?'btn-primary':'btn-secondary')?>">База поставщиков (<?=count($allSuppliers)?>)</a>
            
            <?if(!$showSuppliersTab):?>
                <div style="display: inline-flex; align-items: center; gap: 8px; margin-left: 10px;">
                    <label style="margin: 0; font-weight: bold; font-size: 13px; color: #475569;">Перейти на день:</label>
                    <input type="date" id="logisticCalendarFilter" style="margin: 0; padding: 6px 10px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 4px; max-width: 150px; cursor: pointer;">
                    <button type="button" id="clearCalendarBtn" class="btn-logistic" style="padding: 6px 12px; font-size: 12px; background: #64748b; display: none;">Сбросить</button>
                </div>
            <?endif;?>
        </div>
        <button id="openModalBtn" class="btn-logistic btn-success" style="font-size: 14px; padding: 9px 20px; font-weight: bold; cursor: pointer;">Создать доставку</button>
    </div>

    <!-- УНИВЕРСАЛЬНОЕ МОДАЛЬНОЕ ОКНО -->
    <div id="deliveryModal" class="logistic-modal">
        <div class="logistic-modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:12px; margin-bottom:20px;">
                <h3 id="modalTitle" style="margin:0; color:#007bff; font-size:18px;">Новая логистическая заявка</h3>
                <span id="closeModalBtn" style="color:#aaa; font-size:24px; font-weight:bold; cursor:pointer; line-height:20px;">&times;</span>
            </div>
            
            <form id="ajaxOrderForm" method="POST" action="" class="logistic-form" style="background:transparent; padding:0; border:none; box-shadow:none; margin:0;">
                <input type="hidden" name="action" value="save_order">
                <input type="hidden" id="modal_order_id" name="order_id" value="0">
                <input type="hidden" id="modal_edit_client_only" name="edit_client_only" value="N">
                
                <div class="logistic-form-row">
                    <!-- Блок Отправителя -->
                    <div class="logistic-form-col-6 col-supplier" id="modalSupplierWrapper">
                        <h4>Отправитель / Поставщик</h4>
                        <label>Дата заявки:*</label>
                        <input type="date" id="modal_date" name="date" required value="<?=date('Y-m-d')?>">
                        
                        <label>№ Счета для Покупателя:*</label>
                        <input type="text" id="modal_number" name="number" required placeholder="Наш внутренний счет, например, АВ-154">
                        
                        <label>Компания-поставщик:*</label>
                        <select id="modalSupplierSelect" name="order_supplier_id" required style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #ced4da; border-radius: 4px;">
                            <option value="">-- Выбрать из базы справочника --</option>
                            <?foreach($allSuppliers as $sup):?>
                                <option value="<?=$sup['id']?>"><?=htmlspecialcharsbx($sup['name'])?></option>
                            <?endforeach;?>
                            <option value="MANUAL">-- Ввести вручную (Новый контрагент) --</option>
                        </select>
                        
                        <div id="manualSupplierBlock" style="display:none;">
                            <label>Название новой компании:</label>
                            <input type="text" id="modal_company_supplier" name="company_supplier" placeholder="Введите название юр. лица">
                        </div>
                        
                        <label>Адрес склада загрузки:</label>
                        <input type="text" id="modal_supplier_address" name="supplier_address" placeholder="г. Москва, ул. Ленина 1">
                        
                        <label>Telephone поставщика:</label>
                        <input type="text" id="modal_supplier_phone" name="supplier_phone" placeholder="+7...">
                        
                        <label>Контактное лицо поставщика:</label>
                        <input type="text" id="modal_supplier_manager" name="supplier_manager" placeholder="Имя менеджера">
                        
                        <label style="color:#0284c7;">№ Счета ОТ Поставщика:</label>
                        <input type="text" id="modal_supplier_invoice" name="supplier_invoice" placeholder="Входящий номер счета склада">
                        
                        <label>Комментарий к загрузке:</label>
                        <textarea id="modal_supplier_comment" name="supplier_comment" placeholder="Ворота №3"></textarea>
                    </div>
                    
                    <!-- Блок Получателя -->
                    <div class="logistic-form-col-6 col-client">
                        <h4>Получатель / Клиент</h4>
                        <label>Компания-клиент:</label>
                        <input type="text" id="modal_company_client" name="company_client" placeholder="ООО Клиент">
                        
                        <label>Адрес выгрузки:</label>
                        <input type="text" id="modal_client_address" name="client_address" placeholder="г. Спб, ул. Новая 5">
                        
                        <label>Telephone клиента:</label>
                        <input type="text" id="modal_client_phone" name="client_phone" placeholder="+7...">
                        
                        <label>Контактное лицо клиента:</label>
                        <input type="text" id="modal_client_manager" name="client_manager" placeholder="ФИО Получателя">
                        
                        <label>Комментарий к выгрузке:</label>
                        <textarea id="modal_client_comment" name="client_comment" placeholder="Разгрузка до 18:00"></textarea>
                    </div>
                </div>
                <div style="border-top:1px solid #eee; padding-top:15px; text-align:right;">
                    <button type="button" id="cancelModalBtn" class="btn-logistic btn-secondary" style="margin-right:10px;">Отмена</button>
                    <button type="submit" id="modalSubmitBtn" class="btn-logistic btn-success">Создать заявку</button>
                </div>
            </form>
        </div>
    </div>

    <?if($showSuppliersTab):?>
        <!-- РАЗДЕЛ СПРАВОЧНИКА ПОСТАВЩИКОВ -->
        <div class="logistic-form">
            <h3><?=($editSupplier ? 'Редактировать карточку поставщика' : 'Добавить поставщика в справочник')?></h3>
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
                        <label>Telephone:</label>
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
                                <a href="?view_suppliers=Y&edit_supplier_id=<?=$sup['id']?>" class="btn-action-minimal" title="Правка" style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:#f1f5f9; border:1px solid #cbd5e1; color:#000; border-radius:4px; text-decoration:none; font-size:12px; transition: background 0.15s;">✏️</a>
                                <a href="?view_suppliers=Y&delete_supplier_id=<?=$sup['id']?>" class="btn-action-minimal" title="Удалить" onclick="return confirm('Удалить поставщика из базы?')" style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:#f1f5f9; border:1px solid #cbd5e1; color:#000; border-radius:4px; text-decoration:none; font-size:12px; transition: background 0.15s;">❌</a>
                            </div>
                        </td>
                    </tr>
                <?endforeach;?>
            </tbody>
        </table>
    <?else:?>
        <!-- ЖУРНАЛ СЧЕТОВ С ИНТЕЛЛЕКТУАЛЬНОЙ ГРУППИРОВКОЙ -->
        <div class="logistic-search-box">
            <input type="text" id="orderSearch" class="logistic-search-input" placeholder="🔍 Быстрый поиск (номер, фирма, адрес)...">
        </div>

        <style>
            .modern-action-bar { display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 3px 5px; border-radius: 4px; }
            .btn-modern-act { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 4px; border: 1px solid #cbd5e1; background: #f1f5f9; font-size: 12px; cursor: pointer; transition: background 0.15s ease, border-color 0.15s ease; color: #000 !important; text-decoration: none !important; }
            .btn-modern-act:hover { background: #e2e8f0; border-color: #94a3b8; }
            
            .sort-tower { display: flex; flex-direction: column; gap: 1px; }
            .btn-tower-sort { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 12px; font-size: 8px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #000 !important; text-decoration: none; border-radius: 2px; line-height: 12px; transition: background 0.15s; }
            .btn-tower-sort:hover { background: #e2e8f0; }
            
            .btn-modern-add { background: #f0fdf4; border-color: #86efac; }
            .btn-modern-add:hover { background: #dcfce7; border-color: #22c55e; }
            
            .logistic-table td { vertical-align: top !important; padding: 12px 10px !important; font-size: 12.5px !important; line-height: 1.45 !important; word-break: break-word !important; }
            .supplier-sub-row { border-bottom: 1px dashed #cbd5e1; padding: 12px 10px !important; position: relative; margin: 0; display: block; box-sizing: border-box; }
            .supplier-sub-row:last-child { border-bottom: none; }
        </style>

        <?if(empty($orders)):?>
            <div class="logistic-form" style="text-align:center; padding:30px; color:#666;">Счетов пока нет</div>
        <?else:?>
            <?
            $groupedOrders = [];
            foreach($orders as $order) {
                $dateKey = !empty($order['order_date']) ? date('d.m.Y', strtotime($order['order_date'])) : 'Дата не указана';
                $clientKey = !empty($order['client_address']) ? mb_strtolower(trim($order['client_address'])) : 'Адрес не указан';
                $groupedOrders[$dateKey][$clientKey][] = $order;
            }
            ?>

            <? $dayIndex = 0; ?>
            <?foreach($groupedOrders as $dateBlock => $clientGroups):?>
                <? 
                $dayIndex++; 
                $dayAddressesArray = [];
                $totalOrdersInDay = 0;

                foreach($clientGroups as $clientOrders) {
                    foreach($clientOrders as $order) {
                        $totalOrdersInDay++;
                        if(!empty($order['supplier_address'])) {
                            $addr = trim($order['supplier_address']);
                            if (!in_array($addr, $dayAddressesArray)) $dayAddressesArray[] = $addr;
                        }
                        if(!empty($order['client_address'])) {
                            $addr = trim($order['client_address']);
                            if (!in_array($addr, $dayAddressesArray)) $dayAddressesArray[] = $addr;
                        }
                    }
                }
                $jsonAddresses = json_encode($dayAddressesArray, JSON_UNESCAPED_UNICODE);
                $encodedAddresses = rawurlencode($jsonAddresses);
                $isoDateString = date('Y-m-d', strtotime($dateBlock));
                ?>
                <div class="logistic-day-group" data-day-index="<?=$dayIndex?>" data-day-date="<?=$isoDateString?>" style="margin-bottom: 35px;">
                    
                    <div class="logistic-day-header" style="background: #334155; color: #fff; padding: 10px 15px; font-weight: bold; font-size: 15px; border-radius: 4px 4px 0 0; display: flex; justify-content: space-between; align-items: center;">
                        <span>📅 Счета на день: <?=$dateBlock?></span>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button type="button" class="btn-logistic js-toggle-map-btn" data-addresses="<?=$encodedAddresses?>" style="font-size: 12px; padding: 4px 12px; background: #1a73e8; color: #fff !important; border: none; cursor: pointer; border-radius: 4px;">🗺️ Карта маршрута</button>
                            <a href="?print_day=<?=date('Y-m-d', strtotime($dateBlock))?>" target="_blank" class="btn-logistic btn-success" style="font-size: 12px; padding: 4px 12px; background: #28a745; color: #fff !important; text-decoration: none; border-radius: 4px;">🖨️ Печать дня</a>
                            <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; font-size: 12px;">Всего счетов: <?=$totalOrdersInDay?></span>
                        </div>
                    </div>

                    <table class="logistic-table" style="margin-top: 0; border-top: none; border-radius: 0; margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th style="width: 100px; text-align: center !important;">№ Счет</th>
                                <th style="width: 290px;">🏢 Отправители</th>
                                <th class="col-addr">🏪 Получатель (Точка выгрузки)</th>
                            </tr>
                        </thead>
                        <tbody class="ordersTableDayBody">
                            <?foreach($clientGroups as $clientKey => $items):?>
                                <? 
                                // ЖЕСТКОЕ ИСПРАВЛЕНИЕ: Извлекаем именно первый элемент массива через индекс [0]
                                $firstOrder = $items[0]; 
                                $lastOrderInGroup = end($items);
                                $lastInvoiceNumber = $lastOrderInGroup['order_number'];
                                
                                $uniqueInvoices = array_unique(array_column($items, 'order_number'));
                                $isSingleInvoiceGroup = (count($uniqueInvoices) === 1);
                                ?>
                                <tr class="logistic-tr-item">
                                    <!-- Колонка 1: Номер счета -->
                                    <td style="text-align: center; font-weight: bold; font-size: 13px; background: #f8fafc; border-right: 1px solid #dee2e6; vertical-align: middle !important;">
                                        <?if($isSingleInvoiceGroup):?>
                                            <span style="background: #cbd5e1; padding: 3px 6px; border-radius: 3px; color: #1e293b;"><?=htmlspecialcharsbx($firstOrder['order_number'])?></span>
                                        <?else:?>
                                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                                <?foreach($items as $order):?>
                                                    <span style="background: #cbd5e1; padding: 2px 5px; border-radius: 3px; color: #1e293b; font-size: 11px; white-space: nowrap;">№ <?=htmlspecialcharsbx($order['order_number'])?></span>
                                                <?endforeach;?>
                                            </div>
                                        <?endif;?>
                                    </td>

                                    <!-- Колонка 2: Список Поставщиков -->
                                    <td style="padding: 0 !important; background: #fafafa; border-right: 1px solid #dee2e6;">
                                        <?foreach($items as $order):?>
                                            <div class="supplier-sub-row js-order-data-node" 
                                                 data-id="<?=$order['id']?>" 
                                                 data-date="<?=$order['order_date']?>"
                                                 data-number="<?=htmlspecialcharsbx($order['order_number'])?>"
                                                 data-supplier-id="<?=$order['supplier_id']?>"
                                                 data-comp-sup="<?=htmlspecialcharsbx($order['company_supplier'])?>"
                                                 data-addr-sup="<?=htmlspecialcharsbx($order['supplier_address'])?>"
                                                 data-phone-sup="<?=htmlspecialcharsbx($order['supplier_phone'])?>"
                                                 data-manager-sup="<?=htmlspecialcharsbx($order['supplier_manager'])?>"
                                                 data-invoice-sup="<?=htmlspecialcharsbx($order['supplier_invoice'] ?? '')?>"
                                                 data-comm-sup="<?=htmlspecialcharsbx($order['supplier_comment'])?>"
                                                 data-comp-cli="<?=htmlspecialcharsbx($order['company_client'])?>"
                                                 data-addr-cli="<?=htmlspecialcharsbx($order['client_address'])?>"
                                                 data-phone-cli="<?=htmlspecialcharsbx($order['client_phone'])?>"
                                                 data-manager-cli="<?=htmlspecialcharsbx($order['client_manager'])?>"
                                                 data-comm-cli="<?=htmlspecialcharsbx($order['client_comment'])?>">
                                                
                                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px;">
                                                    <strong style="color: #0284c7; font-size: 13px;"><?=htmlspecialcharsbx($order['company_supplier'])?></strong>
                                                    
                                                    <div class="modern-action-bar" style="transform: scale(0.85); margin-top: -4px; margin-right: -4px;">
                                                        <button type="button" class="btn-modern-act js-trigger-modal-edit" title="Редактировать только этот счет">✏️</button>
                                                        <a href="?delete_id=<?=$order['id']?>" class="btn-modern-act" title="Удалить только этот счет" onclick="return confirm('Удалить этот счет безвозвратно?')">❌</a>
                                                    </div>
                                                </div>
                                                <span style="font-size: 11.5px; color: #334155; display: block; line-height: 1.3;"><?=htmlspecialcharsbx($order['supplier_address'])?></span>
                                                <small class="text-muted" style="color: #475569; display: block; margin-top: 2px;">
                                                    Тел: <?=htmlspecialcharsbx($order['supplier_phone'])?> 
                                                    <?if(!empty($order['supplier_manager'])):?> | Контакт: <?=htmlspecialcharsbx($order['supplier_manager'])?><?endif;?>
                                                </small>
                                                
                                                <?if(!empty($order['supplier_invoice'])):?>
                                                    <div style="margin-top: 3px; font-size: 11px; font-weight: bold; color: #e37400;">
                                                        🧾 Счет от поставщика: <?=htmlspecialcharsbx($order['supplier_invoice'])?>
                                                    </div>
                                                <?endif;?>
                                                
                                                <?if(!empty($order['supplier_comment'])):?>
                                                    <span style="display:block; font-style:italic; color:#64748b; font-size:11px; margin-top:2px;">📋 <?=htmlspecialcharsbx($order['supplier_comment'])?></span>
                                                <?endif;?>
                                            </div>
                                        <?endforeach;?>
                                    </td>
                                    
                                    <!-- Колонка 3: Получатель (Клиент) -->
                                    <td style="vertical-align: middle !important; background: #fff; padding: 15px !important; position: relative;" 
                                        class="js-client-block-node"
                                        data-base-id="<?=$firstOrder['id']?>"
                                        data-date="<?=$isoDateString?>"
                                        data-last-invoice="<?=htmlspecialcharsbx($lastInvoiceNumber)?>"
                                        data-comp-cli="<?=htmlspecialcharsbx($firstOrder['company_client'])?>"
                                        data-addr-cli="<?=htmlspecialcharsbx($firstOrder['client_address'])?>"
                                        data-phone-cli="<?=htmlspecialcharsbx($firstOrder['client_phone'])?>"
                                        data-manager-cli="<?=htmlspecialcharsbx($firstOrder['client_manager'])?>"
                                        data-comm-cli="<?=htmlspecialcharsbx($firstOrder['client_comment'])?>">
                                        
                                        <div style="position: absolute; top: 10px; right: 10px;">
                                            <div class="modern-action-bar">
                                                <div class="sort-tower">
                                                    <a href="?move_id=<?=$firstOrder['id']?>&dir=up" class="btn-tower-sort" title="Двигать всю доставку вверх">▲</a>
                                                    <a href="?move_id=<?=$firstOrder['id']?>&dir=down" class="btn-tower-sort" title="Двигать всю доставку вниз">▼</a>
                                                </div>
                                                <button type="button" class="btn-modern-act btn-modern-add js-trigger-quick-add-supplier" title="Быстро прикрепить еще одного поставщика">➕</button>
                                                <button type="button" class="btn-modern-act js-trigger-client-edit" title="Редактировать только данные покупателя">✏️</button>
                                                <a href="?delete_client_group_id=<?=$firstOrder['id']?>" class="btn-modern-act" title="Удалить ПОЛНОСТЬЮ всю доставку вместе со всеми счетами" onclick="return confirm('Вы уверены, что хотите полностью удалить этого покупателя и все привязанные к нему счета за этот день?')">❌</a>
                                            </div>
                                        </div>

                                        <div style="padding-right: 120px;">
                                            <strong style="font-size: 15px; color: #0f172a;"><?=htmlspecialcharsbx($firstOrder['company_client'])?></strong><br>
                                            <span style="font-weight: bold; color: #1e293b; display: inline-block; margin: 3px 0;"><?=htmlspecialcharsbx($firstOrder['client_address'])?></span><br>
                                            <small style="color:#475569; font-weight: bold; font-size: 12px;">Тел: <?=htmlspecialcharsbx($firstOrder['client_phone'])?></small>
                                            <?if(!empty($firstOrder['client_manager'])):?>
                                                <br><small style="color:#0284c7; font-weight: bold; font-size: 12px;">Контактное лицо: <?=htmlspecialcharsbx($firstOrder['client_manager'])?></small>
                                            <?endif;?>
                                            <?if(!empty($firstOrder['client_comment'])):?>
                                                <div style="margin-top: 8px; font-style: italic; color: #64748b; font-size: 12px; border-left: 2px solid #cbd5e1; padding-left: 8px;">
                                                    <?=htmlspecialcharsbx($firstOrder['client_comment'])?>
                                                </div>
                                            <?endif;?>
                                        </div>
                                    </td>
                                </tr>
                            <?endforeach;?>
                        </tbody>
                    </table>
                </div>
            <?endforeach;?>

            <?if($dayIndex > 3):?>
                <div id="loadMoreDaysWrapper" style="text-align: center; margin: 25px 0 40px 0;">
                    <button type="button" id="loadMoreDaysBtn" class="btn-logistic btn-primary" style="background: #475569; padding: 10px 25px; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">⏳ Показать больше дней</button>
                </div>
            <?endif;?>

        <?endif;?>
    <?endif;?>
</div>
<script type="text/javascript">
window.addEventListener('DOMContentLoaded', function() {
    
    // =========================================================================
    // А. ИНТЕЛЛЕКТУАЛЬНЫЙ СПОЙЛЕР ДНЕЙ ("ПОКАЗАТЬ ЕЩЕ ПО 3 ДНЯ")
    // =========================================================================
    let currentVisibleDays = 3;
    const allDayBlocks = document.querySelectorAll('.logistic-day-group');
    const loadMoreBtn = document.getElementById('loadMoreDaysBtn');
    const calendarFilter = document.getElementById('logisticCalendarFilter');
    const clearCalendarBtn = document.getElementById('clearCalendarBtn');

    function updateDaysVisibility() {
        if (calendarFilter && calendarFilter.value !== '') return;

        allDayBlocks.forEach(block => {
            const idx = parseInt(block.getAttribute('data-day-index'));
            if (idx <= currentVisibleDays) {
                block.style.display = '';
            } else {
                block.style.display = 'none';
            }
        });

        if (loadMoreBtn) {
            if (currentVisibleDays >= allDayBlocks.length) {
                loadMoreBtn.style.display = 'none';
            } else {
                loadMoreBtn.style.display = 'inline-block';
            }
        }
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            currentVisibleDays += 3;
            updateDaysVisibility();
        });
    }

    updateDaysVisibility();

    // =========================================================================
    // Б. ФИЛЬТРАЦИЯ ПО ВСТРОЕННОМУ КАЛЕНДАРЮ
    // =========================================================================
    if (calendarFilter) {
        calendarFilter.addEventListener('change', function() {
            const selectedDate = this.value;

            if (selectedDate === '') {
                if (clearCalendarBtn) clearCalendarBtn.style.display = 'none';
                updateDaysVisibility();
                return;
            }

            if (clearCalendarBtn) clearCalendarBtn.style.display = 'inline-block';
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';

            allDayBlocks.forEach(block => {
                const blockDate = block.getAttribute('data-day-date');
                if (blockDate === selectedDate) {
                    block.style.display = '';
                } else {
                    block.style.display = 'none';
                }
            });
        });
    }

    if (clearCalendarBtn) {
        clearCalendarBtn.addEventListener('click', function() {
            if (calendarFilter) calendarFilter.value = '';
            this.style.display = 'none';
            updateDaysVisibility();
        });
    }

    // =========================================================================
    // В. УПРАВЛЕНИЕ МОДАЛЬНЫМ ОКНОМ (СОЗДАНИЕ, РЕДАКТИРОВАНИЕ, ПРИКРЕПЛЕНИЕ)
    // =========================================================================
    const modal = document.getElementById('deliveryModal');
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const ajaxForm = document.getElementById('ajaxOrderForm');
    
    const modalTitle = document.getElementById('modalTitle');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const modalOrderId = document.getElementById('modal_order_id');
    const modalEditClientOnly = document.getElementById('modal_edit_client_only');
    const modalSupplierWrapper = document.getElementById('modalSupplierWrapper');
    
    const manualBlock = document.getElementById('manualSupplierBlock');
    const manualInput = document.getElementById('modal_company_supplier');
    const supplierSelect = document.getElementById('modalSupplierSelect');

    function setSupplierFieldsRequired(isRequired) {
        if (!modalSupplierWrapper) return;
        modalSupplierWrapper.querySelectorAll('input, select').forEach(input => {
            if (input.id === 'modal_date' || input.id === 'modal_number' || input.id === 'modalSupplierSelect') {
                input.required = isRequired;
            }
            if (input.id === 'modal_company_supplier' && supplierSelect && supplierSelect.value === 'MANUAL') {
                input.required = isRequired;
            }
        });
    }

    // Режим 1: Создание абсолютно новой доставки
    if (openModalBtn) {
        openModalBtn.addEventListener('click', function() {
            if (!modal) return;
            ajaxForm.reset();
            modalOrderId.value = "0";
            modalEditClientOnly.value = "N";
            if (modalSupplierWrapper) modalSupplierWrapper.style.display = 'block';
            setSupplierFieldsRequired(true);
            
            modalTitle.innerText = "📦 Новая логистическая заявка";
            modalSubmitBtn.innerText = "Создать заявку";
            modalSubmitBtn.className = "btn-logistic btn-success";
            if (manualBlock) manualBlock.style.display = 'none';
            
            const dateInput = document.getElementById('modal_date');
            if (dateInput) {
                const today = new Date().toISOString().split('T');
                dateInput.value = today;
            }
            modal.style.display = 'block';
        });
    }

    // Режим 2: Редактирование конкретного счета поставщика [INDEX]
    document.querySelectorAll('.js-trigger-modal-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const dataNode = this.closest('.js-order-data-node');
            if (!dataNode || !modal) return;
            
            ajaxForm.reset();
            modalOrderId.value = dataNode.getAttribute('data-id');
            modalEditClientOnly.value = "N";
            if (modalSupplierWrapper) modalSupplierWrapper.style.display = 'block';
            setSupplierFieldsRequired(true);
            
            modalTitle.innerText = "✏️ Редактирование карточки счета № " + dataNode.getAttribute('data-number');
            modalSubmitBtn.innerText = "Сохранить изменения";
            modalSubmitBtn.className = "btn-logistic btn-primary";
            
            document.getElementById('modal_date').value = dataNode.getAttribute('data-date') || '';
            document.getElementById('modal_number').value = dataNode.getAttribute('data-number') || '';
            
            const supId = dataNode.getAttribute('data-supplier-id');
            if (supId && supId !== '0' && supId !== '') {
                supplierSelect.value = supId;
                if (manualBlock) manualBlock.style.display = 'none';
            } else {
                supplierSelect.value = 'MANUAL';
                if (manualBlock) manualBlock.style.display = 'block';
                if (manualInput) {
                    manualInput.value = dataNode.getAttribute('data-comp-sup') || '';
                    manualInput.required = true;
                }
            }
            
            document.getElementById('modal_supplier_address').value = dataNode.getAttribute('data-addr-sup') || '';
            document.getElementById('modal_supplier_phone').value = dataNode.getAttribute('data-phone-sup') || '';
            document.getElementById('modal_supplier_manager').value = dataNode.getAttribute('data-manager-sup') || '';
            document.getElementById('modal_supplier_invoice').value = dataNode.getAttribute('data-invoice-sup') || ''; // Наполнение счета [INDEX]
            document.getElementById('modal_supplier_comment').value = dataNode.getAttribute('data-comm-sup') || '';
            
            document.getElementById('modal_company_client').value = dataNode.getAttribute('data-comp-cli') || '';
            document.getElementById('modal_client_address').value = dataNode.getAttribute('data-addr-cli') || '';
            document.getElementById('modal_client_phone').value = dataNode.getAttribute('data-phone-cli') || '';
            document.getElementById('modal_client_manager').value = dataNode.getAttribute('data-manager-cli') || '';
            document.getElementById('modal_client_comment').value = dataNode.getAttribute('data-comm-cli') || '';
            
            modal.style.display = 'block';
        });
    });

    // Режим 3: Быстрое прикрепление нового поставщика
    document.querySelectorAll('.js-trigger-quick-add-supplier').forEach(btn => {
        btn.addEventListener('click', function() {
            const clientNode = this.closest('.js-client-block-node');
            if (!clientNode || !modal) return;
            
            ajaxForm.reset();
            modalOrderId.value = "0";
            modalEditClientOnly.value = "N";
            if (modalSupplierWrapper) modalSupplierWrapper.style.display = 'block';
            setSupplierFieldsRequired(true);
            
            modalTitle.innerText = "➕ Прикрепление поставщика для: " + clientNode.getAttribute('data-comp-cli');
            modalSubmitBtn.innerText = "Прикрепить к доставке";
            modalSubmitBtn.className = "btn-logistic btn-success";
            
            document.getElementById('modal_date').value = clientNode.getAttribute('data-date') || '';
            document.getElementById('modal_number').value = clientNode.getAttribute('data-last-invoice') || '';
            document.getElementById('modal_supplier_invoice').value = ''; 
            
            document.getElementById('modal_company_client').value = clientNode.getAttribute('data-comp-cli') || '';
            document.getElementById('modal_client_address').value = clientNode.getAttribute('data-addr-cli') || '';
            document.getElementById('modal_client_phone').value = clientNode.getAttribute('data-phone-cli') || '';
            document.getElementById('modal_client_manager').value = clientNode.getAttribute('data-manager-cli') || '';
            document.getElementById('modal_client_comment').value = clientNode.getAttribute('data-comm-cli') || '';
            
            if (manualBlock) manualBlock.style.display = 'none';
            modal.style.display = 'block';
        });
    });

    // Режим 4: Редактирование ТОЛЬКО данных Покупателя
    document.querySelectorAll('.js-trigger-client-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const clientNode = this.closest('.js-client-block-node');
            if (!clientNode || !modal) return;
            
            ajaxForm.reset();
            modalOrderId.value = clientNode.getAttribute('data-base-id');
            modalEditClientOnly.value = "Y";
            
            if (modalSupplierWrapper) modalSupplierWrapper.style.display = 'none';
            setSupplierFieldsRequired(false);
            
            modalTitle.innerText = "✏️ Редактирование реквизитов покупателя: " + clientNode.getAttribute('data-comp-cli');
            modalSubmitBtn.innerText = "Обновить данные клиента";
            modalSubmitBtn.className = "btn-logistic btn-primary";
            
            document.getElementById('modal_company_client').value = clientNode.getAttribute('data-comp-cli') || '';
            document.getElementById('modal_client_address').value = clientNode.getAttribute('data-addr-cli') || '';
            document.getElementById('modal_client_phone').value = clientNode.getAttribute('data-phone-cli') || '';
            document.getElementById('modal_client_manager').value = clientNode.getAttribute('data-manager-cli') || '';
            document.getElementById('modal_client_comment').value = clientNode.getAttribute('data-comm-cli') || '';
            
            modal.style.display = 'block';
        });
    });

    const closeModal = () => {
        if (modal) modal.style.display = 'none';
        if (ajaxForm) ajaxForm.reset();
    };

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeModal);

    window.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    // =========================================================================
    // Г. НЕПРИКОСНОВЕННЫЙ ОРИГИНАЛЬНЫЙ ЭКСПОРТ В ЯНДЕКС КАРТЫ [INDEX]
    // =========================================================================
    document.querySelectorAll('.js-toggle-map-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const rawData = this.getAttribute('data-addresses');
            if (!rawData) {
                alert('Адреса для этого дня отсутствуют.');
                return;
            }

            try {
                const decodedData = decodeURIComponent(rawData);
                const pointsArray = JSON.parse(decodedData);

                if (!pointsArray || pointsArray.length < 2) {
                    alert('Недостаточно адресов в текущем дне для генерации маршрутного листа.');
                    return;
                }

                const encodedPoints = pointsArray.map(function(pt) {
                    return encodeURIComponent(pt.trim());
                });

                const rtextParam = encodedPoints.join('~');
                const yaDomain = "yandex.ru";
                const finalYandexNavigatorUrl = "https://" + yaDomain + "/maps/?rtext=" + rtextParam + "&rtt=auto";

                window.open(finalYandexNavigatorUrl, '_blank');

            } catch (err) {
                console.error("Ошибка парсинга адресов дня:", err);
                alert('Произошла ошибка при сборке маршрутного листа.');
            }
        });
    });

    if (supplierSelect) {
        supplierSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            if (selectedValue === 'MANUAL') {
                if (manualBlock) manualBlock.style.display = 'block';
                if (manualInput) { manualInput.value = ''; manualInput.required = true; }
                document.getElementById('modal_supplier_address').value = '';
                document.getElementById('modal_supplier_phone').value = '';
                document.getElementById('modal_supplier_manager').value = '';
                document.getElementById('modal_supplier_invoice').value = ''; 
                document.getElementById('modal_supplier_comment').value = '';
                return;
            }

            if (!selectedValue) {
                if (manualBlock) manualBlock.style.display = 'none';
                if (manualInput) { manualInput.value = ''; manualInput.required = false; }
                document.getElementById('modal_supplier_address').value = '';
                document.getElementById('modal_supplier_phone').value = '';
                document.getElementById('modal_supplier_manager').value = '';
                document.getElementById('modal_supplier_invoice').value = ''; 
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
                        document.getElementById('modal_supplier_manager').value = data.manager || '';
                        document.getElementById('modal_supplier_invoice').value = ''; 
                        document.getElementById('modal_supplier_comment').value = data.comment || '';
                    } else {
                        alert('Ошибка подгрузки контрагента: ' + (data.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(() => alert('Сетевая ошибка при получении данных контрагента.'));
        });
    }

    if (ajaxForm) {
        ajaxForm.addEventListener('submit', function() {
            if (modalEditClientOnly && modalEditClientOnly.value === 'Y') return;
            if (supplierSelect && supplierSelect.value !== 'MANUAL' && supplierSelect.value !== '') {
                const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
                let hiddenInput = document.getElementById('modal_company_supplier_hidden');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'modal_company_supplier_hidden';
                    hiddenInput.name = 'company_supplier';
                    this.appendChild(hiddenInput);
                }
                hiddenInput.value = selectedOption.text;
            }
        });
    }

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
