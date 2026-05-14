<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $pdo, $APPLICATION;

if ((isset($_GET['print_id']) && intval($_GET['print_id']) > 0) || (isset($_GET['print_day']) && !empty($_GET['print_day']))) {
    
    $APPLICATION->RestartBuffer();
    
    $ordersToPrint = [];
    $titleText = "";
    
    if (isset($_GET['print_id'])) {
        $orderId = intval($_GET['print_id']);
        $stmt = $pdo->prepare("SELECT * FROM lanmark_logistic_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        if ($row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $ordersToPrint[] = $row;
            $titleText = "Маршрутный лист — Заявка №" . htmlspecialcharsbx($row['order_number']);
        }
    } else {
        $printDate = trim($_GET['print_day']);
        $stmt = $pdo->prepare("SELECT * FROM lanmark_logistic_orders WHERE order_date = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$printDate]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $ordersToPrint[] = array_change_key_case($r, CASE_LOWER);
        }
        $titleText = "Маршрутный лист на день: " . date('d.m.Y', strtotime($printDate));
    }
    
    if (empty($ordersToPrint)) {
        die("<h3 style='color:#000; text-align:center; margin-top:50px;'>Заявки для печати не найдены.</h3>");
    }

    // ГРУППИРОВКА 1: По покупателям (для первой страницы) [INDEX]
    $printGroups = [];
    foreach ($ordersToPrint as $order) {
        $clientKey = !empty($order['client_address']) ? mb_strtolower(trim($order['client_address']), 'UTF-8') : 'Адрес не указан';
        $printGroups[$clientKey][] = $order;
    }

    // ГРУППИРОВКА 2: По поставщикам / складам (для второй страницы) [INDEX]
    $supplierGroups = [];
    foreach ($ordersToPrint as $order) {
        $supKey = !empty($order['supplier_address']) ? mb_strtolower(trim($order['supplier_address']), 'UTF-8') : 'Адрес склада не указан';
        $supplierGroups[$supKey][] = $order;
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title><?=$titleText?></title>
        <style>
            body { font-family: Arial, sans-serif; color: #000 !important; margin: 0; padding: 10px; font-size: 12.5px; background: #fff; line-height: 1.4; }
            .print-wrapper { max-width: 900px; margin: 0 auto; color: #000 !important; }
            
            .no-print-panel { background: #f8fafc; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; display: flex; gap: 15px; align-items: center; border: 1px solid #cbd5e1; color: #000 !important; }
            .btn-print-act { background: #000; color: #fff; border: none; padding: 8px 18px; font-weight: 700; border-radius: 4px; cursor: pointer; font-size: 12px; text-transform: uppercase; }
            
            .print-header { border-bottom: 3px solid #000; padding-bottom: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; color: #000 !important; }
            .print-header h1 { margin: 0; font-size: 17px; font-weight: 700; text-transform: uppercase; color: #000 !important; }
            .company-name { font-size: 15px; font-weight: 800; text-transform: uppercase; color: #000 !important; }
            
            .compact-logistic-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; color: #000 !important; table-layout: fixed; }
            .compact-logistic-table th { border: 2px solid #000; padding: 8px; font-weight: bold; background: #f1f5f9; text-transform: uppercase; font-size: 11px; text-align: left; color: #000 !important; }
            .compact-logistic-table td { border: 1px solid #000; padding: 10px 12px; vertical-align: top; page-break-inside: avoid; color: #000 !important; word-break: break-word !important; overflow-wrap: break-word !important; }
            .compact-logistic-table tr { page-break-inside: avoid; }
            
            .point-title { font-size: 13.5px; font-weight: 700; margin-bottom: 4px; color: #000 !important; line-height: 1.3; }
            .info-row { margin-bottom: 3px; color: #000 !important; display: block; }
            .info-row strong { font-size: 10.5px; text-transform: uppercase; color: #000 !important; display: inline-block; width: 55px; }
            
            .compact-comment { margin-top: 6px; padding: 4px 6px; border-left: 3px solid #000; font-size: 12px; font-style: italic; background: #fff; color: #000 !important; line-height: 1.35; }
            
            .print-supplier-node { border-bottom: 1px dashed #000; padding: 12px 0; color: #000 !important; display: block; }
            .print-supplier-node:last-child { border-bottom: none; padding-bottom: 0; }
            .print-supplier-node:first-child { padding-top: 0; }
            
            .signatures-zone { display: flex; justify-content: space-between; margin-top: 35px; page-break-inside: avoid; color: #000 !important; }
            .sig-block { width: 42%; color: #000 !important; }
            .line-space { border-bottom: 2px solid #000; height: 30px; margin-bottom: 6px; }
            .sig-sub { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #000 !important; }
            
            /* Разрыв страницы для вывода маршрута поставщиков */
            .page-break-block { page-break-before: always; margin-top: 30px; }
            
            @media print {
                .no-print-panel { display: none !important; }
                body { padding: 0; font-size: 12px; color: #000 !important; }
                .compact-logistic-table th { background: #e2e8f0 !important; border: 2px solid #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: #000 !important; }
                .compact-logistic-table td { border: 1px solid #000 !important; color: #000 !important; }
                .compact-comment { background: #fff !important; border-left: 3px solid #000 !important; color: #000 !important; }
            }
        </style>
    </head>
    <body>

        <div class="print-wrapper">
            
            <!-- Панель управления -->
            <div class="no-print-panel">
                <button class="btn-print-act" onclick="window.print();">🖨️ Печать (Ctrl + P)</button>
                <span style="font-size: 12px; font-weight: bold;">Сформирован 2-страничный документ: 1. Выгрузки клиентам; 2. Маршрут по складам загрузки.</span>
            </div>

            <!-- ========================================== -->
            <!-- СТРАНИЦА 1: МАРШРУТ ВЫГРУЗОК ПО КЛИЕНТАМ -->
            <!-- ========================================== -->
            <div class="print-header">
                <h1><?=$titleText?> (Выгрузки)</h1>
                <div class="company-name">ЛАНМАРК</div>
            </div>

            <table class="compact-logistic-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">№</th>
                        <th style="width: 105px; text-align: center;">Счет №</th>
                        <th style="width: 43%;">📦 Пункт А: Загрузки (Поставщики)</th>
                        <th style="width: 43%;">🏪 Пункт Б: Выгрузка / Общий Клиент</th>
                    </tr>
                </thead>
                <tbody>
                <? $cardNum = 1; ?>
                <? foreach($printGroups as $clientKey => $items): ?>
                    <? 
                    $firstOrder = $items[0]; 
                    $uniqueInvoices = array_unique(array_column($items, 'order_number'));
                    $isSingleInvoiceGroup = (count($uniqueInvoices) === 1);
                    ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold; font-size: 15px; vertical-align: middle; background: #fff; border-right: 2px solid #000; color: #000 !important;"><?=$cardNum?></td>
                        
                        <td style="text-align: center; font-weight: bold; font-size: 13px; vertical-align: middle; background: #fff; border-right: 2px solid #000; color: #000 !important;">
                            <?if($isSingleInvoiceGroup):?>
                                <span><?=htmlspecialcharsbx($firstOrder['order_number'])?></span>
                            <?else:?>
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <?foreach($items as $order):?>
                                        <span style="border-bottom: 1px dotted #000; padding-bottom: 2px; white-space: nowrap;">№ <?=htmlspecialcharsbx($order['order_number'])?></span>
                                    <?endforeach;?>
                                </div>
                            <?endif;?>
                        </td>

                        <td style="color: #000 !important; vertical-align: top;">
                            <?foreach($items as $order):?>
                                <div class="print-supplier-node">
                                    <div class="point-title"><?=htmlspecialcharsbx($order['company_supplier'])?></div>
                                    <div class="info-row"><strong>Адрес:</strong> <?=htmlspecialcharsbx($order['supplier_address'])?></div>
                                    <div class="info-row"><strong>Тел:</strong> <?=htmlspecialcharsbx($order['supplier_phone'])?></div>
                                    
                                    <!-- Модифицировано под требования ФИО [INDEX] -->
                                    <?if(!empty($order['supplier_manager'])):?>
                                        <div class="info-row"><strong>ФИО:</strong> <?=htmlspecialcharsbx($order['supplier_manager'])?></div>
                                    <?endif;?>
                                    
                                    <!-- Модифицировано под требования Счёт [INDEX] -->
                                    <?if(!empty($order['supplier_invoice'])):?>
                                        <div class="info-row">
                                            <strong>Счет:</strong> <?=htmlspecialcharsbx($order['supplier_invoice'])?>
                                        </div>
                                    <?endif;?>
                                    
                                    <?if(!empty($order['supplier_comment'])):?>
                                        <div class="compact-comment">📋 <?=htmlspecialcharsbx($order['supplier_comment'])?></div>
                                    <?endif;?>
                                </div>
                            <?endforeach;?>
                        </td>
                        
                        <td style="vertical-align: middle !important; color: #000 !important;">
                            <div class="point-title" style="font-size: 14px;"><?=htmlspecialcharsbx($firstOrder['company_client'])?></div>
                            <div class="info-row" style="font-size: 12.5px; font-weight: bold;"><strong>Адрес:</strong> <?=htmlspecialcharsbx($firstOrder['client_address'])?></div>
                            <div class="info-row"><strong>Тел:</strong> <?=htmlspecialcharsbx($firstOrder['client_phone'])?></div>
                            
                            <!-- Модифицировано под требования ФИО [INDEX] -->
                            <?if(!empty($firstOrder['client_manager'])):?>
                                <div class="info-row"><strong>ФИО:</strong> <?=htmlspecialcharsbx($firstOrder['client_manager'])?></div>
                            <?endif;?>
                            
                            <?if(!empty($firstOrder['client_comment'])):?>
                                <div class="compact-comment">📋 <?=htmlspecialcharsbx($firstOrder['client_comment'])?></div>
                            <?endif;?>
                        </td>
                    </tr>
                    <? $cardNum++; ?>
                <? endforeach; ?>
                </tbody>
            </table>

            <div class="signatures-zone">
                <div class="sig-block"><div class="line-space"></div><div class="sig-sub">Диспетчер / Логист (Выдал)</div></div>
                <div class="sig-block"><div class="line-space"></div><div class="sig-sub">Водитель / Экспедитор (Принял)</div></div>
            </div>


            <!-- ========================================== -->
            <!-- СТРАНИЦА 2: ВНЕДРЕНО МАРШРУТ ЗАГРУЗОК ПО ПОСТАВЩИКАМ -->
            <!-- ========================================== -->
            <div class="page-break-block"></div>
            
            <div class="print-header">
                <h1><?=$titleText?> (Склады загрузки)</h1>
                <div class="company-name">ЛАНМАРК</div>
            </div>

            <table class="compact-logistic-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">№</th>
                        <th style="width: 200px;">🏢 Поставщик / Склад</th>
                        <th style="width: 140px;">🧾 Забираемые счета</th>
                        <th>📍 Реквизиты и Комментарий загрузки</th>
                    </tr>
                </thead>
                <tbody>
                <? $supNum = 1; ?>
                <? foreach($supplierGroups as $supKey => $items): ?>
                    <? $baseSup = $items[0]; ?>
                    <tr>
                        <!-- Номер по порядку -->
                        <td style="text-align: center; font-weight: bold; font-size: 15px; vertical-align: middle; background: #fff;"><?= $supNum ?></td>
                        
                        <!-- Название Поставщика -->
                        <td style="vertical-align: middle;">
                            <div class="point-title" style="font-size: 14px;"><?= htmlspecialcharsbx($baseSup['company_supplier']) ?></div>
                        </td>
                        
                        <!-- Перечисление всех входящих счетов от поставщика, собранных на этом складе [INDEX] -->
                        <td style="vertical-align: top; background: #fafafa;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <? foreach($items as $order): ?>
                                    <? if(!empty($order['supplier_invoice'])): ?>
                                        <span style="font-weight: bold; border-bottom: 1px dotted #000; padding-bottom: 2px;">📄 Счет: <?= htmlspecialcharsbx($order['supplier_invoice']) ?></span>
                                    <? else: ?>
                                        <span style="font-style: italic; font-size: 11px; color:#555;">Без номера счета</span>
                                    <? endif; ?>
                                <? endforeach; ?>
                            </div>
                        </td>
                        
                        <!-- Точный Адрес, Телефон, ФИО и Заметка склада загрузки -->
                        <td>
                            <div class="info-row"><strong>Адрес:</strong> <?= htmlspecialcharsbx($baseSup['supplier_address']) ?></div>
                            <div class="info-row"><strong>Тел:</strong> <?= htmlspecialcharsbx($baseSup['supplier_phone']) ?></div>
                            <? if(!empty($baseSup['supplier_manager'])): ?>
                                <div class="info-row"><strong>ФИО:</strong> <?= htmlspecialcharsbx($baseSup['supplier_manager']) ?></div>
                            <? endif; ?>
                            
                            <? 
                            // Собираем все комментарии к загрузке этого склада в один блок, если они разные [INDEX]
                            $supComments = [];
                            foreach($items as $order) {
                                if(!empty($order['supplier_comment'])) {
                                    $c = trim($order['supplier_comment']);
                                    if(!in_array($c, $supComments)) $supComments[] = $c;
                                }
                            }
                            ?>
                            <? if(!empty($supComments)): ?>
                                <div class="compact-comment">
                                    <strong>Указания к загрузке:</strong><br>
                                    <?= implode('<br>', array_map('htmlspecialcharsbx', $supComments)) ?>
                                </div>
                            <? endif; ?>
                        </td>
                    </tr>
                    <? $supNum++; ?>
                <? endforeach; ?>
                </tbody>
            </table>

            <div class="signatures-zone" style="margin-top: 40px;">
                <div class="sig-block"><div class="line-space"></div><div class="sig-sub">Сдал (Склад / Поставщик)</div></div>
                <div class="sig-block"><div class="line-space"></div><div class="sig-sub">Принял (Водитель / Экспедитор)</div></div>
            </div>

        </div> <!-- .print-wrapper -->
    </body>
    </html>
    <?
    die();
}
?>
