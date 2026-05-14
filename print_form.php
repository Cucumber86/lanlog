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
            body { font-family: -apple-system, Arial, sans-serif; color: #111; margin: 0; padding: 20px; font-size: 13.5px; background: #fff; line-height: 1.4; }
            .print-wrapper { max-width: 850px; margin: 0 auto; }
            
            /* Кнопка управления */
            .no-print-panel { background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 25px; display: flex; gap: 15px; align-items: center; border: 1px solid #cbd5e1; }
            .btn-print-act { background: #0f172a; color: #fff; border: none; padding: 8px 18px; font-weight: 600; border-radius: 4px; cursor: pointer; font-size: 13px; text-transform: uppercase; }
            .btn-print-act:hover { background: #1e293b; }
            
            /* Чистый заголовок */
            .print-header { border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-end; }
            .print-header h1 { margin: 0; font-size: 19px; font-weight: 700; color: #000; text-transform: uppercase; letter-spacing: -0.3px; }
            .company-name { font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #000; text-align: right; line-height: 1; }
            
            /* Карточки доставок */
            .job-card { border: 2px solid #000; border-radius: 4px; margin-bottom: 20px; overflow: hidden; page-break-inside: avoid; background: #fff; }
            
            /* ОТДЕЛЬНАЯ ЯЧЕЙКА (ПЛАШКА) ДЛЯ НОМЕРА ЗАЯВКИ НАД КОНТЕНТОМ */
            .job-card-header-row { background: #f1f5f9; padding: 6px 16px; border-bottom: 2px solid #000; font-weight: bold; font-size: 14px; color: #0f172a; }
            
            .job-card-body { display: flex; }
            
            /* Блок нумерации (Светлая подложка, темный текст) */
            .driver-check { width: 50px; border-right: 2px solid #000; display: flex; align-items: center; justify-content: center; background: #f1f5f9; font-size: 22px; font-weight: 800; color: #0f172a; }
            
            /* Блоки загрузки и выгрузки */
            .point-block { flex: 1; padding: 14px 16px; box-sizing: border-box; }
            .point-block:first-child { border-right: 1px solid #000; }
            
            .firm-type { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #000; margin-bottom: 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px; }
            .firm-name { font-size: 15px; font-weight: 700; margin-bottom: 8px; color: #000; }
            
            .info-row { margin-bottom: 4px; color: #000; font-size: 13.5px; }
            .info-row strong { font-size: 11px; text-transform: uppercase; color: #334155; display: inline-block; width: 55px; font-weight: 700; }
            
            .comment-box { margin-top: 10px; background: #f8fafc; border: 1px solid #000; padding: 8px 10px; font-size: 12px; border-radius: 4px; color: #000; font-style: italic; }
            
            /* Карта */
            #printMap { width: 100%; height: 480px; border: 2px solid #000; border-radius: 4px; margin-bottom: 25px; background: #fafafa; page-break-inside: avoid; }
            
            /* Футер */
            .signatures-zone { display: flex; justify-content: space-between; margin-top: 35px; page-break-inside: avoid; }
            .sig-block { width: 42%; }
            .line-space { border-bottom: 2px solid #000; height: 35px; margin-bottom: 6px; }
            .sig-sub { font-size: 11px; color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
            
            @media print {
                .no-print-panel { display: none !important; }
                body { padding: 0; font-size: 12.5px; color: #000; }
                .job-card { border: 2px solid #000 !important; }
                .job-card-header-row { background: #e2e8f0 !important; border-bottom: 2px solid #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .driver-check { background: #f1f5f9 !important; color: #0f172a !important; border-right: 2px solid #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .point-block:first-child { border-right: 1px solid #000 !important; }
                .comment-box { background: #fff !important; border: 1px dashed #000 !important; }
                #printMap { border: 2px solid #000 !important; }
                .line-space { border-bottom: 2px solid #000 !important; }
            }
        </style>
    </head>
    <body>

        <div class="print-wrapper">
            
            <!-- Управляющая панель -->
            <div class="no-print-panel">
                <button class="btn-print-act" onclick="window.print();">🖨️ Печать (Ctrl + P)</button>
                <span style="font-size: 12px; color: #475569; font-weight: 500;">Шаблон адаптирован под монохромную печать. Масштаб карты регулируется автоматически.</span>
            </div>

            <!-- Шапка бланка -->
            <div class="print-header">
                <h1><?=$titleText?></h1>
                <div class="company-name">ЛАНМАРК</div>
            </div>

            <!-- СПИСОК ДОСТАВОК -->
            <? $cardNum = 1; ?>
            <? foreach($ordersToPrint as $order): ?>
                <div class="job-card">
                    <!-- ЗАМЕНЕНО: НАЗВАНИЕ ДЛЯ НОМЕРА ЗАЯВКИ СТАЛО «СЧЕТ:» -->
                    <div class="job-card-header-row">
                        <span>СЧЕТ: № <?=htmlspecialcharsbx($order['order_number'])?></span>
                    </div>

                    <div class="job-card-body">
                        <!-- Левый блок нумерации -->
                        <div class="driver-check">
                            <div><?=$cardNum?></div>
                        </div>
                        
                        <!-- Блок Загрузки -->
                        <div class="point-block">
                            <div class="firm-type">Загрузка</div>
                            <div class="firm-name"><?=htmlspecialcharsbx($order['company_supplier'])?></div>
                            <div class="info-row"><strong>Адрес:</strong> <?=htmlspecialcharsbx($order['supplier_address'])?></div>
                            <div class="info-row"><strong>Тел:</strong> <?=htmlspecialcharsbx($order['supplier_phone'])?></div>
                            <?if(!empty($order['supplier_comment'])):?>
                                <div class="comment-box"><?=htmlspecialcharsbx($order['supplier_comment'])?></div>
                            <?endif;?>
                        </div>
                        
                        <!-- Блок Выгрузки -->
                        <div class="point-block">
                            <div class="firm-type">Выгрузка</div>
                            <div class="firm-name"><?=htmlspecialcharsbx($order['company_client'])?></div>
                            <div class="info-row"><strong>Адрес:</strong> <?=htmlspecialcharsbx($order['client_address'])?></div>
                            <div class="info-row"><strong>Тел:</strong> <?=htmlspecialcharsbx($order['client_phone'])?></div>
                            <?if(!empty($order['client_comment'])):?>
                                <div class="comment-box"><?=htmlspecialcharsbx($order['client_comment'])?></div>
                            <?endif;?>
                        </div>
                    </div>
                </div>
                <? $cardNum++; ?>
            <? endforeach; ?>

            <!-- КАРТА С МАРШРУТОМ -->
            <div id="printMap"></div>

            <!-- Зона подписей -->
            <div class="signatures-zone">
                <div class="sig-block">
                    <div class="line-space"></div>
                    <div class="sig-sub">Диспетчер-логист</div>
                </div>
                <div class="sig-block">
                    <div class="line-space"></div>
                    <div class="sig-sub">Водитель-экспедитор</div>
                </div>
            </div>

        </div>

        <?
        $yandexDomain = "api-maps." . "yandex.ru";
        $yandexVersion = "2.1";
        $yandexKey = "d2dfaf47-545d-4840-99b7-462fa04e9113";
        $yandexLang = "ru_RU";
        $finalApiUrl = "https://" . $yandexDomain . "/" . $yandexVersion . "/?apikey=" . $yandexKey . "&lang=" . $yandexLang;
        ?>
        <script src="<?=$finalApiUrl?>" type="text/javascript"></script>

        <!-- МАРШРУТИЗАЦИЯ ПО КООРДИНАТАМ -->
        <script type="text/javascript">
            ymaps.ready(function() {
                const mapElement = document.getElementById("printMap");
                if(!mapElement) return;

                const myMap = new ymaps.Map("printMap", {
                    center: [55.76, 37.64],
                    zoom: 10,
                    controls: [] 
                });

                const rawPoints = [];
                <? foreach($ordersToPrint as $order): ?>
                    <? if(!empty($order['supplier_address'])): ?>
                        <? 
                        $addr = trim($order['supplier_address']);
                        $addr = str_replace(["\r", "\n", '"', "'", '`', '\\'], ' ', $addr);
                        $addr = preg_replace('/\s+/', ' ', $addr);
                        ?>
                        rawPoints.push("<?=CUtil::JSEscape($addr)?>");
                    <? endif; ?>
                    <? if(!empty($order['client_address'])): ?>
                        <? 
                        $addr = trim($order['client_address']);
                        $addr = str_replace(["\r", "\n", '"', "'", '`', '\\'], ' ', $addr);
                        $addr = preg_replace('/\s+/', ' ', $addr);
                        ?>
                        rawPoints.push("<?=CUtil::JSEscape($addr)?>");
                    <? endif; ?>
                <? endforeach; ?>

                const geoCoordinates = [];

                function geocodeNext(index) {
                    if (index >= rawPoints.length) {
                        buildRoute(geoCoordinates);
                        return;
                    }

                    ymaps.geocode(rawPoints[index], { results: 1 }).then(function (res) {
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
                        geocodeNext(index + 1);
                    }, function () {
                        geocodeNext(index + 1);
                    });
                }

                function buildRoute(coordsArray) {
                    if (coordsArray.length < 2) return;
                    const multiRoute = new ymaps.multiRouter.MultiRoute({
                        referencePoints: coordsArray,
                        params: { routingMode: 'auto' }
                    }, {
                        boundsAutoApply: true,
                        wayPointVisible: false 
                    });
                    myMap.geoObjects.add(multiRoute);
                    setTimeout(function() { myMap.container.fitToViewport(); }, 400);
                }

                geocodeNext(0);
            });
        </script>
    </body>
    </html>
    <?
    die();
}
?>
