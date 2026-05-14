<?
// 1. ИНИЦИАЛИЗАЦИЯ ЯДРА БИТРИКСА ДЛЯ ПРОВЕРКИ АВТОРИЗАЦИИ АДМИНИСТРАТОРА
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Логистический комплекс ЛАНМАРК — Экспресс Маршруты");

// 2. СТРОГАЯ ПРОВЕРКА ДОСТУПА
global $USER;
if (!$USER->IsAuthorized() || !in_array(1, $USER->GetUserGroupArray())) {
    echo '<div class="alert alert-danger" style="color:red; font-weight:bold; padding:20px;">Доступ запрещен.</div>';
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
    die();
}

// ------------------------------------------------------------------------
// ОБРАБОТЧИК ПОИСКА АДРЕСА НА СЕРВЕРЕ (PHP PROXY)
// ------------------------------------------------------------------------
if (isset($_GET['ajax_search_address']) && !empty($_GET['ajax_search_address'])) {
    $APPLICATION->RestartBuffer();
    $address = trim($_GET['ajax_search_address']);
    
    // Мягкий поиск координат через 2GIS
    $url = 'https://2gis.com' . urlencode($address) . '&key=general&fields=items.point';
    
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    header('Content-Type: application/json; charset=utf-8');
    if ($response === false) {
        echo json_encode(['error' => 'fail']);
    } else {
        echo $response;
    }
    die();
}
?>

<style>
    .crm-container { font-family: Arial, sans-serif; padding: 20px; background-color: #fdfdfd; border: 1px solid #e3e3e3; margin-top: 20px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
    .form-control { width: 100%; max-width: 500px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    
    .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; color: #fff; }
    .btn-primary { background: #007bff; } 
    .btn-success { background: #ffcc00; color: #000; } /* Желтый Яндекс */
    .btn-danger { background: #dc3545; }
    
    .modal-window { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; }
    .modal-content { background: #fff; width: 90%; max-width: 550px; margin: 50px auto; padding: 25px; border-radius: 6px; position: relative; }
    .route-item { display: flex; align-items: center; justify-content: space-between; padding: 10px; border: 1px solid #eee; margin-bottom: 5px; border-radius: 4px; background: #fff; }
    .arrow-btn { padding: 2px 6px; cursor: pointer; margin-left: 2px; }
</style>

<div class="crm-container">
    <h2>Логистический комплекс ЛАНМАРК</h2>
    <h3>Быстрое формирование путевых листов для Яндекс.Карт</h3>
    
    <div style="margin-bottom: 15px;">
        <button class="btn btn-primary" id="openModalBtn">+ Создать заявку</button>
    </div>

    <!-- Таблица заявок -->
    <div style="margin-bottom: 20px;">
        <h4>Очередность точек в путевом листе:</h4>
        <div id="pointsContainer" style="margin-bottom: 10px;"></div>
        <div id="emptyNotify" style="color: #666; font-style: italic;">Список пуст. Добавьте точки разгрузки.</div>
    </div>

    <!-- Кнопки управления -->
    <div>
        <button class="btn btn-success" id="openYandexRouteBtn" style="display:none; font-size: 15px;">Открыть готовый маршрут в Яндекс.Картах</button>
        <button class="btn btn-danger" id="clearListBtn" style="display:none; margin-left: 10px; font-size: 15px;">Очистить все</button>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО СОЗДАНИЯ ЗАЯВКИ -->
<div class="modal-window" id="orderModal">
    <div class="modal-content">
        <span id="closeModalBtn" style="position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer; font-weight:bold;">&times;</span>
        <h3>Новая логистическая заявка</h3>
        
        <div class="form-group">
            <label>ФИО / Компания Клиента:</label>
            <input type="text" id="cClient" class="form-control" placeholder="ООО Трейд / Иванов И.И.">
        </div>
        <div class="form-group">
            <label>Телефон:</label>
            <input type="text" id="cPhone" class="form-control" placeholder="+7 (999) 123-45-67">
        </div>
        <div class="form-group">
            <label>Адрес доставки (Пишите в любом формате):</label>
            <input type="text" id="cAddress" class="form-control" placeholder="Москва, Новый Арбат, 21">
        </div>
        <div class="form-group">
            <label>Комментарий:</label>
            <textarea id="cComment" class="form-control" rows="3" placeholder="Разгрузка до 18:00..."></textarea>
        </div>
        
        <div style="text-align: right; margin-top: 15px;">
            <button class="btn btn-primary" id="saveOrderBtn">Добавить в путевой лист</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    var routePoints = []; 

    window.addEventListener('DOMContentLoaded', function() {
        
        // 1. МОДАЛЬНОЕ ОКНО
        document.getElementById('openModalBtn').addEventListener('click', function() {
            document.getElementById('orderModal').style.display = 'block';
        });
        document.getElementById('closeModalBtn').addEventListener('click', closeModal);
        
        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
            document.getElementById('cClient').value = '';
            document.getElementById('cPhone').value = '';
            document.getElementById('cAddress').value = '';
            document.getElementById('cComment').value = '';
        }

        // 2. БЕЗОШИБОЧНОЕ ДОБАВЛЕНИЕ АДРЕСА
        document.getElementById('saveOrderBtn').addEventListener('click', function() {
            var client = document.getElementById('cClient').value.trim();
            var phone = document.getElementById('cPhone').value.trim();
            var comment = document.getElementById('cComment').value.trim();
            var addressText = document.getElementById('cAddress').value.trim();

            if (!client || !addressText) {
                alert('Заполните обязательные поля: Клиент и Адрес.');
                return;
            }

            var currentUrl = window.location.pathname + '?ajax_search_address=' + encodeURIComponent(addressText);

            // Пробуем тихо фоном найти координаты в 2GIS
            fetch(currentUrl)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    var finalPointValue = addressText; // По умолчанию передаем просто текст

                    // Если 2GIS нашел точные координаты, подставим их для идеальной точности
                    if (data && data.result && data.result.items && data.result.items.length > 0) {
                        var firstItem = data.result.items[0]; // Исправлено чтение первого элемента в JS
                        if (firstItem && firstItem.point) {
                            finalPointValue = firstItem.point.lat + ',' + firstItem.point.lon;
                        }
                    }

                    // ВСЕГДА добавляем заявку, независимо от ответа базы!
                    routePoints.push({
                        client: client,
                        phone: phone,
                        comment: comment,
                        address: addressText,
                        pointData: finalPointValue // Здесь либо координаты, либо ваш текст
                    });

                    closeModal();
                    renderCrmList();
                })
                .catch(function(err) {
                    // Если даже сервер упал — все равно добавляем по вашему тексту!
                    routePoints.push({
                        client: client,
                        phone: phone,
                        comment: comment,
                        address: addressText,
                        pointData: addressText
                    });

                    closeModal();
                    renderCrmList();
                });
        });

        // 3. ОТРИСОВКА СПИСКА
        window.renderCrmList = function() {
            var container = document.getElementById('pointsContainer');
            var emptyNotify = document.getElementById('emptyNotify');
            var yandexBtn = document.getElementById('openYandexRouteBtn');
            var clearBtn = document.getElementById('clearListBtn');

            container.innerHTML = '';

            if (routePoints.length === 0) {
                container.style.display = 'none'; emptyNotify.style.display = 'block';
                yandexBtn.style.display = 'none'; clearBtn.style.display = 'none';
                return;
            }

            container.style.display = 'block'; emptyNotify.style.display = 'none';
            yandexBtn.style.display = 'inline-block'; clearBtn.style.display = 'inline-block';

            routePoints.forEach(function(item, index) {
                var letter = String.fromCharCode(65 + index);
                var isStart = (index === 0);

                var itemHtml = document.createElement('div');
                itemHtml.className = 'route-item';
                if (isStart) {
                    itemHtml.style.backgroundColor = '#fff3cd';
                    itemHtml.style.borderLeft = '4px solid #ffc107';
                }

                itemHtml.innerHTML = `
                    <div>
                        <strong>[Точка ${letter}] ${isStart ? 'СТАРТ' : 'КЛИЕНТ'}:</strong> ${item.client} | 
                        <span>${item.address}</span> <br>
                        <small style="color:#666;">Тел: ${item.phone || 'не указан'} | Заметка: ${item.comment || 'нет'}</small>
                    </div>
                    <div>
                        <button class="arrow-btn" onclick="moveCrmItem(${index}, ${index - 1})" ${isStart ? 'disabled' : ''}>▲</button>
                        <button class="arrow-btn" onclick="moveCrmItem(${index}, ${index + 1})" ${index === routePoints.length - 1 ? 'disabled' : ''}>▼</button>
                        <button class="arrow-btn" onclick="deleteCrmItem(${index})" style="background:#dc3545; color:#fff; border:none; border-radius:3px;">✕</button>
                    </div>
                `;
                container.appendChild(itemHtml);
            });
        };

        // Функции сортировки
        window.moveCrmItem = function(fromIndex, toIndex) {
            var target = routePoints[fromIndex];
            routePoints.splice(fromIndex, 1);
            routePoints.splice(toIndex, 0, target);
            renderCrmList();
        };

        window.deleteCrmItem = function(index) {
            routePoints.splice(index, 1);
            renderCrmList();
        };

        document.getElementById('clearListBtn').addEventListener('click', function() {
            routePoints = [];
            renderCrmList();
        });

        // 4. СБОРКА И СТАРТ В ЯНДЕКС КАРТАХ
        document.getElementById('openYandexRouteBtn').addEventListener('click', function() {
            if (routePoints.length < 2) {
                alert('Добавьте минимум две точки.');
                return;
            }

            // Кодируем параметры для Яндекс URL
            var rtextParam = routePoints.map(function(item) {
                return encodeURIComponent(item.pointData);
            }).join('~');
            
            var yaDomain = "yandex." + "ru";
            var finalYandexUrl = "https://" + yaDomain + "/maps/?rtext=" + rtextParam + "&rtt=auto";

            window.open(finalYandexUrl, '_blank');
        });
    });
</script>

<?
// 9. ЗАКРЫТИЕ ЯДРА БИТРИКСА (ВЫВОД ФУТЕРА САЙТА)
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
