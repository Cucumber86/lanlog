<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<div class="logistic-container">

    <!-- Вкладки верхнего навигационного меню -->
    <div style="margin-bottom: 20px;">
        <a href="?view_archive=N" class="btn-logistic <?=((!$showSuppliersTab && $showArchive=='N')?'btn-primary':'btn-secondary')?>">?? Текущие заявки</a>
        <a href="?view_archive=Y" class="btn-logistic <?=((!$showSuppliersTab && $showArchive=='Y')?'btn-primary':'btn-secondary')?>">?? Архив заявок</a>
        <a href="?view_suppliers=Y" class="btn-logistic <?=($showSuppliersTab?'btn-primary':'btn-secondary')?>">?? База поставщиков (<?=count($allSuppliers)?>)</a>
    </div>

    <?if($showSuppliersTab):?>
        <!-- БЛОК 1: ИНТЕРФЕЙС РАЗДЕЛА СПРАВОЧНИКА ПОСТАВЩИКОВ -->
        <div class="logistic-form">
            <h3><?=($editSupplier ? '? Редактировать карточку поставщика' : '?? Добавить поставщика в справочник')?></h3>
            <form method="POST">
                <input type="hidden" name="supplier_action" value="<?=($editSupplier ? 'edit' : 'add')?>">
                <?if($editSupplier):?><input type="hidden" name="s_id" value="<?=$editSupplier['id']?>"><?endif;?>
                <div class="logistic-form-row">
                    <div class="logistic-form-col-6">
                        <label>Название юр. лица:</label>
                        <input type="text" name="s_name" value="<?=($editSupplier ? $editSupplier['name'] : '')?>" required placeholder="ООО МеталлТрейд">
                        <label>Точный адрес склада:</label>
                        <input type="text" name="s_address" value="<?=($editSupplier ? $editSupplier['address'] : '')?>" required placeholder="г. Москва, ул. Заводская, д. 2">
                        <label>ФИО Менеджера:</label>
                        <input type="text" name="s_manager" value="<?=($editSupplier ? $editSupplier['manager'] : '')?>" placeholder="Иванов Александр">
                    </div>
                    <div class="logistic-form-col-6">
                        <label>Телефон:</label>
                        <input type="text" name="s_phone" class="js-phone-input" value="<?=($editSupplier ? $editSupplier['phone'] : '+7 ')?>">
                        <label>E-mail:</label>
                        <input type="email" name="s_email" value="<?=($editSupplier ? $editSupplier['email'] : '')?>" placeholder="manager@supplier.ru">
                        <label>Постоянная заметка (номера ворот, специфика):</label>
                        <textarea name="s_comment" placeholder="Въезд под шлагбаум, склад №5"><?=($editSupplier ? $editSupplier['comment'] : '')?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-logistic btn-success"><?=($editSupplier ? 'Сохранить изменения' : 'Внести в базу контрагентов')?></button>
                <?if($editSupplier):?><a href="?view_suppliers=Y" class="btn-logistic btn-secondary" style="margin-left:10px;">Отмена</a><?endif;?>
            </form>
        </div>

        <div class="logistic-search-box">
            <input type="text" id="skladSearch" class="logistic-search-input" placeholder="?? Быстрый поиск в справочнике...">
        </div>

        <table class="logistic-table">
            <thead>
                <tr><th>Поставщик</th><th>Адрес склада</th><th>Менеджер</th><th>Контакты</th><th>Заметка</th><th style="width:75px;">Действия</th></tr>
            </thead>
            <tbody id="logisticTableBody">
                <?foreach($allSuppliers as $sup):?>
                    <tr class="logistic-tr-item">
                        <td><strong style="font-size:15px; color:#1a73e8;"><?=$sup['name']?></strong></td>
                        <td><?=$sup['address']?></td><td><?=$sup['manager']?></td>
                        <td><?=$sup['phone']?><br><small><?=$sup['email']?></small></td><td><?=$sup['comment']?></td>
                        <td>
                            <div class="action-btn-group">
                                <a href="?view_suppliers=Y&edit_supplier_id=<?=$sup['id']?>" class="btn-action-small btn-warning" title="Правка">?</a>
                                <a href="?view_suppliers=Y&delete_supplier_id=<?=$sup['id']?>" class="btn-action-small btn-danger" title="Удалить" onclick="return confirm('Удалить поставщика из базы?')">?</a>
                            </div>
                        </td>
                    </tr>
                <?endforeach;?>
            </tbody>
        </table>

    <?else:?>
        <!-- БЛОК 2: ИНТЕРФЕЙС ЖУРНАЛА ЛОГИСТИЧЕСКИХ ЗАЯВОК -->
        <div class="logistic-form">
            <h3>? Создать новую логистическую заявку</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_order">
                
                <div class="logistic-form-row">
                    <div class="logistic-form-col-3">
                        <label>Дата:</label>
                        <input type="date" name="date" value="<?=date('Y-m-d')?>" required>
                    </div>
                    <div class="logistic-form-col-3">
                        <label>Номер заказа:</label>
                        <input type="text" name="number" required placeholder="ЛНК-105">
                    </div>
                    <div class="logistic-form-col-3">
                        <label>Статус:</label>
                        <select name="label_status">
                            <option value="PROCESSING">?? В работе</option>
                            <option value="DELIVERY">?? Доставка</option>
                            <option value="COMPLETED">?? Выполнен</option>
                        </select>
                    </div>
                </div>

                <div class="logistic-form-row">
                    <!-- Пункт забора (Слева) с выпадающим списком из MySQL -->
                    <div class="logistic-form-col-6 col-supplier">
                        <h4>?? 1. ПУНКТ ЗАБОРА (ПОСТАВЩИК)</h4>
                        <label style="color:#1a73e8;">Выбрать готового поставщика из базы контрагентов:</label>
                        <select id="formSupplierSelect" name="order_supplier_id" onchange="jsAutoFillSupplier();">
                            <option value="0">-- Выбрать контрагента (или ввести вручную ниже) --</option>
                            <?foreach($allSuppliers as $sup):?>
                                <option value="<?=$sup['id']?>" data-address="<?=$sup['address']?>" data-phone="<?=$sup['phone']?>" data-comment="<?=$sup['comment']?>"><?=$sup['name']?></option>
                            <?endforeach;?>
                        </select>

                        <label>Название юр. лица:</label>
                        <input type="text" id="order_company_supplier" name="company_supplier" required>
                        <label>Точный адрес забора:</label>
                        <input type="text" id="order_supplier_address" name="supplier_address" required>
                        <label>Телефон ответственного:</label>
                        <input type="text" id="order_supplier_phone" name="supplier_phone" class="js-phone-input" value="+7 ">
                        <label>Комментарий к забору (счета, пометки):</label>
                        <textarea id="order_supplier_comment" name="supplier_comment"></textarea>
                    </div>

                    <!-- Пункт доставки (Справа) -->
                    <div class="logistic-form-col-6 col-client">
                        <h4>?? 2. ПУНКТ ДОСТАКИ (КЛИЕНТ)</h4>
                        <label>Название юр. лица клиента:</label>
                        <input type="text" name="company_client" required placeholder="ИП Иванов И.И.">
                        <label>Точный адрес объекта доставки:</label>
                        <input type="text" name="client_address" required placeholder="г. Москва, Тверской б-р, 12">
                        <label>Телефон получателя:</label>
                        <input type="text" name="client_phone" class="js-phone-input" value="+7 ">
                        <label>Комментарий к доставке (пропуск, разгрузка):</label>
                        <textarea name="client_comment"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-logistic btn-success">Провести и сохранить заявку</button>
            </form>
        </div>

        <div class="logistic-search-box">
            <input type="text" id="skladSearch" class="logistic-search-input" placeholder="?? CRM-поиск по номеру, счетам, компании, комментариям...">
        </div>
                <!-- Интерактивная таблица заявок -->
        <table class="logistic-table">
            <thead>
                <tr>
                    <th class="col-date">Дата</th>
                    <th class="col-label">Ярлык</th>
                    <th class="col-num">Номер</th>
                    <th class="col-addr">Поставщик / Забор</th>
                    <th class="col-addr">Клиент / Доставка</th>
                    <th class="col-act">Действия</th>
                </tr>
            </thead>
            <tbody id="logisticTableBody">
                <?foreach($orders as $order):?>
                    <?
                    $badgeClass = 'badge-none'; $labelText = 'Нет ярлыка';
                    if ($order['order_label'] == 'PROCESSING') { $badgeClass = 'badge-processing'; $labelText = 'В работе'; }
                    if ($order['order_label'] == 'DELIVERY') { $badgeClass = 'badge-delivery'; $labelText = 'Доставка'; }
                    if ($order['order_label'] == 'COMPLETED') { $badgeClass = 'badge-completed'; $labelText = 'Выполнен'; }
                    ?>
                    <tr class="logistic-tr-item" id="order_row_<?=$order['id']?>">
                        <td><?=date('d.m.Y', strtotime($order['order_date']))?></td>
                        
                        <!-- AJAX-ярлыки METOК -->
                        <td>
                            <div class="label-dropdown">
                                <span id="badge_text_<?=$order['id']?>" class="gmail-badge <?=$badgeClass?>"><?=$labelText?></span>
                                <ul class="label-menu">
                                    <li><a href="#" onclick="jsSetLabel(<?=$order['id']?>, 'PROCESSING', 'В работе', 'badge-processing'); return false;">?? В работе</a></li>
                                    <li><a href="#" onclick="jsSetLabel(<?=$order['id']?>, 'DELIVERY', 'Доставка', 'badge-delivery'); return false;">?? Доставка</a></li>
                                    <li><a href="#" onclick="jsSetLabel(<?=$order['id']?>, 'COMPLETED', 'Выполнен', 'badge-completed'); return false;">?? Выполнен</a></li>
                                    <li style="border-top:1px solid #eee;"><a href="#" onclick="jsSetLabel(<?=$order['id']?>, 'clear', 'Нет ярлыка', 'badge-none'); return false;" style="color:#6c757d;">Сбросить</a></li>
                                </ul>
                            </div>
                        </td>
                        
                        <!-- Номер заказа -->
                        <td>
                            <span class="view-mode-<?=$order['id']?>"><b><?=$order['order_number']?></b></span>
                            <input type="text" class="edit-mode-<?=$order['id']?> ajax-edit-input" value="<?=$order['order_number']?>" style="display:none;" id="edit_num_<?=$order['id']?>">
                        </td>
                        
                        <!-- Забор -->
                        <td>
                            <div class="view-mode-<?=$order['id']?>">
                                <strong style="color:#1a73e8; font-size:15px;"><?=$order['company_supplier']?></strong><br>
                                <span><?=$order['supplier_address']?></span><br>
                                <small style="color:#6c757d; font-weight:bold;"><?=$order['supplier_phone']?></small>
                                <?if(!empty($order['supplier_comment'])):?><div style="margin-top:6px; padding:4px 8px; background:#fff3cd; border-left:3px solid #ffc107; font-size:12px;"><b>Пометка:</b> <?=$order['supplier_comment']?></div><?endif;?>
                            </div>
                            <div class="edit-mode-<?=$order['id']?>" style="display:none; flex-direction:column; gap:4px; width:100%;">
                                <input type="text" class="ajax-edit-input" value="<?=$order['company_supplier']?>" id="edit_s_comp_<?=$order['id']?>" placeholder="Компания">
                                <input type="text" class="ajax-edit-input" value="<?=$order['supplier_address']?>" id="edit_s_addr_<?=$order['id']?>" placeholder="Адрес забора">
                                <input type="text" class="ajax-edit-input js-phone-input" value="<?=$order['supplier_phone']?>" id="edit_s_phone_<?=$order['id']?>" placeholder="Телефон">
                                <textarea class="ajax-edit-textarea" id="edit_s_comm_<?=$order['id']?>" placeholder="Комментарий / Счета"><?=$order['supplier_comment']?></textarea>
                            </div>
                        </td>
                        
                        <!-- Доставка -->
                        <td>
                            <div class="view-mode-<?=$order['id']?>">
                                <strong style="color:#28a745; font-size:15px;"><?=$order['company_client']?></strong><br>
                                <span><?=$order['client_address']?></span><br>
                                <small style="color:#6c757d; font-weight:bold;"><?=$order['client_phone']?></small>
                                <?if(!empty($order['client_comment'])):?><div style="margin-top:6px; padding:4px 8px; background:#e2f0d9; border-left:3px solid #28a745; font-size:12px;"><b>Пометка:</b> <?=$order['client_comment']?></div><?endif;?>
                            </div>
                            <div class="edit-mode-<?=$order['id']?>" style="display:none; flex-direction:column; gap:4px; width:100%;">
                                <input type="text" class="ajax-edit-input" value="<?=$order['company_client']?>" id="edit_c_comp_<?=$order['id']?>" placeholder="Клиент">
                                <input type="text" class="ajax-edit-input" value="<?=$order['client_address']?>" id="edit_c_addr_<?=$order['id']?>" placeholder="Адрес доставки">
                                <input type="text" class="ajax-edit-input js-phone-input" value="<?=$order['client_phone']?>" id="edit_c_phone_<?=$order['id']?>" placeholder="Телефон получателя">
                                <textarea class="ajax-edit-textarea" id="edit_c_comm_<?=$order['id']?>" placeholder="Инструкции разгрузки"><?=$order['client_comment']?></textarea>
                            </div>
                        </td>
                        
                        <!-- Кнопки управления -->
                        <td>
                            <div class="action-btn-group view-mode-<?=$order['id']?>">
                                <div style="display:flex; flex-direction:column; gap:3px;">
                                    <button type="button" class="btn-action-small btn-warning" onclick="jsInlineEditStart(<?=$order['id']?>);" title="Быстрая правка">?</button>
                                    <a href="?move_id=<?=$order['id']?>&dir=up<?=($showArchive=='Y'?'&view_archive=Y':'')?>" class="btn-action-small btn-secondary" style="background:#4a5568;" title="Вверх">^</a>
                                    <a href="?move_id=<?=$order['id']?>&dir=down<?=($showArchive=='Y'?'&view_archive=Y':'')?>" class="btn-action-small btn-secondary" style="background:#4a5568;" title="Вниз">Ў</a>
                                </div>
                                <div style="display:flex; flex-direction:column; gap:3px;">
                                    <?if($showArchive == 'N'):?><a href="?archive_id=<?=$order['id']?>" class="btn-action-small btn-secondary">??</a>
                                    <?else:?><a href="?unarchive_id=<?=$order['id']?>&view_archive=Y" class="btn-action-small btn-success">?</a><?endif;?>
                                    <a href="?delete_id=<?=$order['id']?><?=($showArchive=='Y'?'&view_archive=Y':'')?>" class="btn-action-small btn-danger" onclick="return confirm('Удалить?')">?</a>
                                </div>
                            </div>
                            <div class="action-btn-group edit-mode-<?=$order['id']?>" style="display:none; flex-direction:column; gap:5px;">
                                <button type="button" class="btn-logistic btn-success" style="padding:4px 8px; font-size:12px; width:100%;" onclick="jsInlineEditSave(<?=$order['id']?>);">?? Сохранить</button>
                                <button type="button" class="btn-logistic btn-secondary" style="padding:4px 8px; font-size:12px; width:100%; background:#718096;" onclick="jsInlineEditCancel(<?=$order['id']?>);">? Отмена</button>
                            </div>
                        </td>
                    </tr>
                <?endforeach;?>
            </tbody>
        </table>

        <!-- ССЫЛКА НА ЯНДЕКС КАРТЫ С ЖЕСТКОЙ КОРРЕКЦИЕЙ ОШИБОК -->
        <div style="margin-bottom: 30px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <?if($showArchive == 'N' && count($orders) >= 1):?>
                <?
                $cleanedPoints = array();
                foreach ($orders as $order) {
                    if (!empty($order['supplier_address'])) {
                        $addr = trim($order['supplier_address']);
                        $addr = preg_replace('/[^\w\s\d,.-]/u', '', $addr);
                        $cleanedPoints[] = urlencode($addr);
                    }
                    if (!empty($order['client_address'])) {
                        $addr = trim($order['client_address']);
                        $addr = preg_replace('/[^\w\s\d,.-]/u', '', $addr);
                        $cleanedPoints[] = urlencode($addr);
                    }
                }
                $yandexUrl = "https://yandex.ru" . implode('~', $cleanedPoints) . "&rtt=mt";
                ?>
                <a href="<?=$yandexUrl?>" target="_blank" class="btn-logistic" style="background: #ffcc00; color: #000; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #e6b800;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    ?? Открыть карту в Яндекс (Печать / Навигатор)
                </a>
                <a href="?print_route=Y" target="_blank" class="btn-logistic btn-success" style="display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    ?? Печать ТТН и Рейсов
                </a>
                <small style="color: #64748b; font-size: 13px;">?? Очередность в Яндексе зависит от положения строк в таблице. Меняйте их кнопками ^ и Ў.</small>
            <?endif;?>
        </div>
    <?endif;?>
</div>

<script type="text/javascript">
// АВТОПОДСТАНОВКА ИЗ БАЗЫ
function jsAutoFillSupplier() {
    var select = document.getElementById("formSupplierSelect"); if(!select) return;
    var opt = select.options[select.selectedIndex];
    if (opt && opt.value !== "0") {
        document.getElementById("order_company_supplier").value = opt.text;
        document.getElementById("order_supplier_address").value = opt.getAttribute("data-address");
        document.getElementById("order_supplier_phone").value = opt.getAttribute("data-phone");
        document.getElementById("order_supplier_comment").value = opt.getAttribute("data-comment");
    } else {
        document.getElementById("order_company_supplier").value = "";
        document.getElementById("order_supplier_address").value = "";
        document.getElementById("order_supplier_phone").value = "+7 ";
        document.getElementById("order_supplier_comment").value = "";
    }
}

// AJAX ЯРЛЫКИ
function jsSetLabel(elementId, labelXmlId, labelText, cssClass) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '?ajax_action=set_label&set_label=' + encodeURIComponent(labelXmlId) + '&element_id=' + elementId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    var badge = document.getElementById('badge_text_' + elementId);
                    if (badge) { badge.className = 'gmail-badge ' + cssClass; badge.innerHTML = labelText; }
                } else { alert('Ошибка сохранения.'); }
            } catch(e) { alert('Ошибка ответа.'); }
        }
    };
    xhr.send();
}

// AJAX ИНЛАЙН РЕДАКТОР
function jsInlineEditStart(id) {
    document.querySelectorAll('.view-mode-' + id).forEach(el => el.style.display = 'none');
    document.querySelectorAll('.edit-mode-' + id).forEach(el => el.style.display = 'flex');
}

function jsInlineEditCancel(id) {
    document.querySelectorAll('.edit-mode-' + id).forEach(el => el.style.display = 'none');
    document.querySelectorAll('.view-mode-' + id).forEach(el => el.style.display = '');
}

function jsInlineEditSave(id) {
    var formData = new FormData();
    formData.append('ajax_action', 'save_order_row');
    formData.append('element_id', id);
    formData.append('order_number', document.getElementById('edit_num_' + id).value);
    formData.append('company_supplier', document.getElementById('edit_s_comp_' + id).value);
    formData.append('supplier_address', document.getElementById('edit_s_addr_' + id).value);
    formData.append('supplier_phone', document.getElementById('edit_s_phone_' + id).value);
    formData.append('supplier_comment', document.getElementById('edit_s_comm_' + id).value);
    formData.append('company_client', document.getElementById('edit_c_comp_' + id).value);
    formData.append('client_address', document.getElementById('edit_c_addr_' + id).value);
    formData.append('client_phone', document.getElementById('edit_c_phone_' + id).value);
    formData.append('client_comment', document.getElementById('edit_c_comm_' + id).value);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) { window.location.reload(); } else { alert('Ошибка записи.'); }
            } catch(e) { window.location.reload(); }
        }
    };
    xhr.send(formData);
}

document.addEventListener("DOMContentLoaded", function() {
    // Живой CRM-поиск
    var searchInput = document.getElementById("skladSearch");
    if(searchInput) {
        searchInput.addEventListener("input", function() {
            var filter = searchInput.value.toLowerCase().trim();
            var rows = document.querySelectorAll(".logistic-tr-item");
            rows.forEach(function(row) {
                if(row.textContent.toLowerCase().indexOf(filter) > -1) { row.style.display = ""; } else { row.style.display = "none"; }
            });
        });
    }

    // Маска +7
    var phoneInputs = document.querySelectorAll('.js-phone-input');
    phoneInputs.forEach(function(input) {
        input.addEventListener('focus', function() { if (input.value.trim() === '') { input.value = '+7 '; } });
        input.addEventListener('input', function() { if (!input.value.startsWith('+7 ')) { input.value = '+7 ' + input.value.replace(/^\+?7?\s?/, ''); } });
        input.addEventListener('click', function() { if (input.selectionStart < 3) { input.setSelectionRange(3, 3); } });
    });
});
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>

