<?
// 1. ИНИЦИАЛИЗАЦИЯ ЯДРА БИТРИКСА ДЛЯ ПРОВЕРКИ АВТОРИЗАЦИИ АДМИНИСТРАТОРА
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Логистический комплекс ЛАНМАРК (Модульный CRM)");

// 2. СТРОГАЯ ПРОВЕРКА ДОСТУПА
global $USER;
if (!$USER->IsAuthorized() || !in_array(1, $USER->GetUserGroupArray())) {
    echo '<div class="alert alert-danger" style="color:red; font-weight:bold; padding:20px;">Доступ запрещен. Страница только для администраторов.</div>';
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
    die();
}

$errorMsg = '';

// 3. БЕЗОПАСНОЕ ПОДКЛЮЧЕНИЕ К ИЗОЛИРОВАННОЙ БАЗЕ ДАННЫХ ЧЕРЕЗ PDO
try {
    $dsn = "mysql:host=localhost;dbname=lanmark_logistics;charset=utf8";
    $pdo = new PDO($dsn, "lanmark_logistics", "Nlluzjdc210986", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Ошибка подключения к БД lanmark_logistics: " . $e->getMessage() . "</div>");
}

// 4. ПОДКЛЮЧЕНИЕ ВНЕШНЕГО ФАЙЛА СТИЛЕЙ К СТРАНИЦЕ ЧЕРЕЗ API БИТРИКСА
$APPLICATION->SetAdditionalCSS($APPLICATION->GetCurDir() . "style.css");

// 5. ПОДКЛЮЧЕНИЕ МОДУЛЯ АСИНХРОННЫХ AJAX-ОБРАБОТЧИКОВ (ЯРЛЫКИ И ИНЛАЙН-ПРАВКА)
include(__DIR__ . "/ajax_handlers.php");

// 6. ПОДКЛЮЧЕНИЕ МОДУЛЯ ПРОДВИНУТОЙ ПЕЧАТНОЙ ФОРМЫ ВОДИТЕЛЯ
include(__DIR__ . "/print_form.php");

// 7. ПОДКЛЮЧЕНИЕ МОДУЛЯ ОБРАБОТКИ СТАНДАРТНЫХ ОПЕРАЦИЙ БАЗЫ ДАННЫХ (CRUD)
include(__DIR__ . "/db_actions.php");

// 8. ПОДКЛЮЧЕНИЕ МОДУЛЯ ПОЛЬЗОВАТЕЛЬСКОГО CRM-ИНТЕРФЕЙСА (ФОРМЫ И ТАБЛИЦЫ)
include(__DIR__ . "/crm_interface.php");

// 9. ЗАКРЫТИЕ ЯДРА БИТРИКСА (ВЫВОД ФУТЕРА САЙТА)
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
