<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// ПРОДВИНУТАЯ КОНТЕЙНЕРНАЯ ПЕЧАТНАЯ ФОРМА (ГРУППИРОВКА ПО ЗАКАЗЧИКАМ)
if (isset($_GET['print_route']) && $_GET['print_route'] == 'Y') {
    $APPLICATION->RestartBuffer(); // Отключаем дизайн сайта Битрикс полностью
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Группированный маршрутный лист водителя — ООО ЛАНМАРК</title>
        <style>
            @import url('https://googleapis.com');
            body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; padding: 40px; color: #1e293b; font-size: 14px; line-height: 1.5; background: #f8fafc; }
            .print-header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
            .print-header h2 { margin: 0 0 5px 0; font-size: 22px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; }
            .print-header .meta-info { font-size: 13px; color: #64748b; text-align: right; line-height: 1.6; }
            .print-header .meta-info b { color: #0f172a; }
            .route-container-box { background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); page-break-inside: avoid; overflow: hidden; }
            .route-box-header { background: #0f172a; color: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
            .route-box-header .client-title { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; }
            .route-box-header .order-numbers { font-size: 13px; background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 4px; font-weight: 600; }
            .route-steps-table { width: 100%; border-collapse: collapse; }
            .route-steps-table th { background: #f1f5f9; text-transform: uppercase; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; color: #475569; padding: 10px 15px; border-bottom: 1px solid #cbd5e1; text-align: left; }
            .route-steps-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; vertical-align: top; text-align: left !important; }
            .route-steps-table tr:last-child td { border-bottom: none; }
            .step-badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; }
            .badge-pickup { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
            .badge-unload { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
            .comment-card { margin-top: 6px; padding: 8px 12px; background: #f8fafc; border-left: 3px solid #94a3b8; font-size: 12px; border-radius: 3px; color: #334155; }
            .comment-card b { color: #0f172a; }
            .btn-print-trigger { background: #0f172a; color: #fff; border: none; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; margin-bottom: 25px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
            .print-footer { margin-top: 40px; display: flex; justify-content: space-between; page-break-inside: avoid; }
            .signature-block { width: 30%; border-top: 1px solid #94a3b8; padding-top: 8px; font-size: 12px; color: #64748b; text-align: center; margin-top: 30px; }
            @media print { 
                .btn-print-trigger { display: none; } body { padding: 0; background: #fff; color: #000; }
                .route-container-box { border: 2px solid #000 !important; margin-bottom: 30px; }
                .route-box-header { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .route-steps-table th { background: #e2e8f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .step-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000 !important; }
                .comment-card { background: #f8fafc !important; border-left: 3px solid #000 !important; }
            }
        </style>
    </head>
    <body>
        <button class="btn-print-trigger" onclick="window.print();">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Распечатать маршрутный лист (Ctrl + P)
        </button>
        
        <div class="print-header">
            <div>
                <h2>Маршрутный лист водителя (Порейсовый)</h2>
                <span style="font-size: 14px; color: #475569; font-weight: 500;">ООО «ЛАНМАРК» &nbsp;&middot;&nbsp; Модуль сквозной b2b-группировки</span>
            </div>
            <div class="meta-info">
                <b>Дата рейса:</b> <?=date('d.m.Y')?><br>
                <b>Время сборки:</b> <?=date('H:i')?>
            </div>
        </div>

        <?
        $stmt = $pdo->query("SELECT * FROM lanmark_logistic_orders WHERE is_archive = 'N' ORDER BY sort_order ASC, order_date DESC");
        $rawOrders = $stmt->fetchAll();

        $groupedRoutes = array();
        foreach ($rawOrders as $order) {
            $clientKey = trim($order['company_client']);
            if (!isset($groupedRoutes[$clientKey])) {
                $groupedRoutes[$clientKey] = array(
                    'client_name' => $order['company_client'], 'client_address' => $order['client_address'],
                    'client_phone' => $order['client_phone'], 'client_comment' => $order['client_comment'],
                    'order_numbers' => array(), 'pickups' => array()
                );
            }
            if (!in_array($order['order_number'], $groupedRoutes[$clientKey]['order_numbers'])) {
                $groupedRoutes[$clientKey]['order_numbers'][] = $order['order_number'];
            }
            if (!empty($order['supplier_address'])) {
                $groupedRoutes[$clientKey]['pickups'][] = array(
                    'order_number' => $order['order_number'], 'company_supplier' => $order['company_supplier'],
                    'supplier_address' => $order['supplier_address'], 'supplier_phone' => $order['supplier_phone'],
                    'supplier_comment' => $order['supplier_comment']
                );
            }
        }

        $boxIndex = 1;
        foreach ($groupedRoutes as $clientName => $route) {
            ?>
            <div class="route-container-box">
                <div class="route-box-header">
                    <div class="client-title">РЕЙС №<?=$boxIndex?> &nbsp;&middot;&nbsp; ПОЛУЧАТЕЛЬ: <?=$route['client_name']?></div>
                    <div class="order-numbers">Заказы: <?=implode(', ', $route['order_numbers'])?></div>
                </div>
                <table class="route-steps-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Операция</th>
                            <th style="width: 220px;">Контрагент</th>
                            <th>Адрес объекта назначения</th>
                            <th style="width: 250px;">Контакты диспетчера</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?
                        $stepNum = 1;
                        foreach ($route['pickups'] as $pickup) {
                            ?>
                            <tr>
                                <td><span class="step-badge badge-pickup">Шаг <?=$stepNum?>. Забор</span></td>
                                <td><strong style="color: #0f172a;"><?=$pickup['company_supplier']?></strong><br><small style="color: #64748b;">(к заказу <?=$pickup['order_number']?>)</small></td>
                                <td><b><?=$pickup['supplier_address']?></b></td>
                                <td>
                                    <span style="font-weight: 600; color: #0f172a;"><?=$pickup['supplier_phone']?></span>
                                    <?if(!empty($pickup['supplier_comment'])):?>
                                        <div class="comment-card"><b>Документы / Счета:</b> <?=$pickup['supplier_comment']?></div>
                                    <?endif;?>
                                </td>
                            </tr>
                            <?
                            $stepNum++;
                        }
                        ?>
                        <tr style="background: #f8fafc;">
                            <td><span class="step-badge badge-unload">Финал. Доставка</span></td>
                            <td><strong style="color: #15803d; font-size: 15px;"><?=$route['client_name']?></strong></td>
                            <td><b style="color: #15803d; font-size: 15px;"><?=$route['client_address']?></b></td>
                            <td>
                                <span style="font-weight: 700; color: #15803d;"><?=$route['client_phone']?></span>
                                <?if(!empty($route['client_comment'])):?>
                                    <div class="comment-card" style="border-left-color: #28a745;"><b>Инструкция разгрузки:</b> <?=$route['client_comment']?></div>
                                <?endif;?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?
            $boxIndex++;
        }
        ?>
        <div class="print-footer">
            <div class="signature-block">Логист-диспетчер (Выдал)</div>
            <div class="signature-block">Водитель-экспедитор (Принял)</div>
            <div class="signature-block">Фактическая дата закрытия рейса</div>
        </div>
    </body>
    </html>
    <?
    die();
}
?>
