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
            $ordersToPrint[] = $row;
            $titleText = "Маршрутный лист — Заявка №" . htmlspecialcharsbx($row['order_number']);
        }
    } else {
        $printDate = trim($_GET['print_day']);
        $stmt = $pdo->prepare("SELECT * FROM lanmark_logistic_orders WHERE order_date = ? AND is_archive = 'N' ORDER BY sort_order ASC");
        $stmt->execute([$printDate]);
        $ordersToPrint = $stmt->fetchAll();
        $titleText = "Маршрутный лист на день: " . date('d.m.Y', strtotime($printDate));
    }
    
    if (empty($ordersToPrint)) {
        die("<h3 style='color:red; text-align:center; margin-top:50px;'>Заявки для печати не найдены.</h3>");
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title><?=$titleText?></title>
        <style>
            body { font-family: -apple-system, Arial, sans-serif; color: #000; margin: 0; padding: 10px; font-size: 11.5px; background: #fff; line-height: 1.25; }
            .print-wrapper { max-width: 900px; margin: 0 auto; }
            
            /* Панель управления */
            .no-print-panel { background: #f8fafc; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; display: flex; gap: 15px; align-items: center; border: 1px solid #cbd5e1; }
            .btn-print-act { background: #0f172a; color: #fff; border: none; padding: 6px 14px; font-weight: 600; border-radius: 4px; cursor: pointer; font-size: 11px; text-transform: uppercase; }
            
            /* Компактная шапка */
            .print-header { border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-end; }
            .print-header h1 { margin: 0; font-size: 15px; font-weight: 700; text-transform: uppercase; }
            .company-name { font-size: 14px; font-weight: 800; text-transform: uppercase; }
            
            /* СВЕРХКОМПАКТНАЯ ТАБЛИЦА МАРШРУТА */
            .compact-logistic-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .compact-logistic-table th { border: 2px solid #000; padding: 5px; font-weight: bold; background: #f1f5f9; text-transform: uppercase; font-size: 10.5px; text-align: left; }
            .compact-logistic-table td { border: 1px solid #000; padding: 5px 6px; vertical-align: top; page-break-inside: avoid; }
            .compact-logistic-table tr { page-break-inside: avoid; }
            
            /* Внутренние элементы таблицы */
            .point-title { font-size: 12px; font-weight: 700; margin-bottom: 2px; color: #000; }
            .info-row { margin-bottom: 2px; }
            .info-row strong { font-size: 10px; text-transform: uppercase; color: #475569; inline-block; width: 45px; }
            .compact-comment { margin-top: 4px; padding: 3px 5px; border-left: 2px solid #000; font-size: 10.5px; font-style: italic; background: #f8fafc; }
            
            /* Подписи */
            .signatures-zone { display: flex; justify-content: space-between; margin-top: 25px; page-break-inside: avoid; }
            .sig-block { width: 42%; }
            .line-space { border-bottom: 1px solid #000; height: 25px; margin-bottom: 4px; }
            .sig-sub { font-size: 10px; font-weight: 700; text-transform: uppercase; }
            
            @media print {
                .no-print-panel { display: none !important; }
                body { padding: 0; font-size: 11px; }
                .compact-logistic-table th { background: #e2e8f0 !important; border: 2px solid #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .compact-logistic-table td { border: 1px solid #000 !important; }
                .compact-comment { background: #fff !important; border-left: 2px solid #000 !important; }
            }
        </style>
    </head>
    <body>

        <div class="print-wrapper">
            
            <!-- Управляющая панель -->
            <div class="no-print-panel">
                <button class="btn-print-act" onclick="window.print();">🖨️ Печать (Ctrl + P)</button>
                <span style="font-size: 11px; color: #475569; font-weight: 500;">Включен режим максимальной плотности строк для длинных путевых листов.</span>
            </div>

            <!-- Шапка бланка -->
            <div class="print-header">
                <h1><?=$titleText?></h1>
                <div class="company-name">ЛАНМАРК</div>
            </div>

            <!-- ТАБЛИЦА СЧЕТОВ -->
            <table class="compact-logistic-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">№ п/п</th>
                        <th style="width: 75px; text-align: center;">Счет №</th>
                        <th style="width: 44%;">📦 Пункт А: Загрузка / Поставщик</th>
                        <th style="width: 44%;">🏪 Пункт Б: Выгрузка / Клиент</th>
                    </tr>
                </thead>
                <tbody>
                <? $cardNum = 1; ?>
                <? foreach($ordersToPrint as $order): ?>
                    <tr>
                        <!-- Номер по порядку -->
                        <td style="text-align: center; font-weight: bold; font-size: 14px; vertical-align: middle; background: #fafafa; border-right: 2px solid #000;"><?=$cardNum?></td>
                        
                        <!-- Номер счета -->
                        <td style="text-align: center; font-weight: bold; vertical-align: middle; border-right: 2px solid #000;"><?=htmlspecialcharsbx($order['order_number'])?></td>
                        
                        <!-- Точка А: Поставщик -->
                        <td>
                            <div class="point-title"><?=htmlspecialcharsbx($order['company_supplier'])?></div>
                            <div class="info-row"><strong>Адрес:</strong> <?=htmlspecialcharsbx($order['supplier_address'])?></div>
                            <div class="info-row"><strong>Тел:</strong> <?=htmlspecialcharsbx($order['supplier_phone'])?></div>
                            <?if(!empty($order['supplier_comment'])):?>
                                <div class="compact-comment">📋 <?=htmlspecialcharsbx($order['supplier_comment'])?></div>
                            <?endif;?>
                        </div>
                        
                        <!-- Точка Б: Клиент -->
                        <td>
                            <div class="point-title"><?=htmlspecialcharsbx($order['company_client'])?></div>
                            <div class="info-row"><strong>Адрес:</strong> <?=htmlspecialcharsbx($order['client_address'])?></div>
                            <div class="info-row"><strong>Тел:</strong> <?=htmlspecialcharsbx($order['client_phone'])?></div>
                            <?if(!empty($order['client_comment'])):?>
                                <div class="compact-comment">📋 <?=htmlspecialcharsbx($order['client_comment'])?></div>
                            <?endif;?>
                        </td>
                    </tr>
                    <? $cardNum++; ?>
                <? endforeach; ?>
                </tbody>
            </table>

            <!-- ЗОНА ПОДПИСЕЙ СТОРОН -->
            <div class="signatures-zone">
                <div class="sig-block">
                    <div class="line-space"></div>
                    <div class="sig-sub">Диспетчер / Логист (Выдал)</div>
                </div>
                <div class="sig-block">
                    <div class="line-space"></div>
                    <div class="sig-sub">Водитель / Экспедитор (Принял)</div>
                </div>
            </div>

        </div> <!-- .print-wrapper -->
    </body>
    </html>
    <?
    die();
}
?>
