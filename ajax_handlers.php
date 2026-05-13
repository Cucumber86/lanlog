<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// 1. ФОНОВЫЙ AJAX-ОБРАБОТЧИК ДЛЯ СМЕНЫ ЯРЛЫКОВ «НА ЛЕТУ»
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'set_label') {
    $APPLICATION->RestartBuffer(); // Отключаем весь внешний HTML Битрикса
    
    $elementID = intval($_GET['element_id']);
    $labelXmlId = htmlspecialcharsbx($_GET['set_label']);
    
    $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET order_label = ? WHERE id = ?");
    $success = $stmt->execute([$labelXmlId, $elementID]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'label' => $labelXmlId]);
    die(); // Прерываем выполнение, чтобы отдать чистый JSON
}

// 2. ФОНОВЫЙ AJAX-ОБРАБОТЧИК ДЛЯ ИНЛАЙН-РЕДАКТИРОВАНИЯ СТРОКИ ЗАЯВКИ
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_order_row') {
    $APPLICATION->RestartBuffer(); // Отключаем внешний HTML Битрикса
    
    $elementID = intval($_POST['element_id']);
    
    $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET order_number = ?, company_supplier = ?, supplier_address = ?, supplier_phone = ?, supplier_comment = ?, company_client = ?, client_address = ?, client_phone = ?, client_comment = ? WHERE id = ?");
    $success = $stmt->execute([
        htmlspecialcharsbx($_POST['order_number']), 
        htmlspecialcharsbx($_POST['company_supplier']),
        htmlspecialcharsbx($_POST['supplier_address']), 
        htmlspecialcharsbx($_POST['supplier_phone']),
        htmlspecialcharsbx($_POST['supplier_comment']), 
        htmlspecialcharsbx($_POST['company_client']),
        htmlspecialcharsbx($_POST['client_address']), 
        htmlspecialcharsbx($_POST['client_phone']),
        htmlspecialcharsbx($_POST['client_comment']), 
        $elementID
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    die(); // Прерываем выполнение
}
?
    // 3. ФОНОВЫЙ AJAX-ОБРАБОТЧИК ДЛЯ СОЗДАНИЯ НОВОЙ ЗАЯВКИ ИЗ МОДАЛЬНОГО ОКНА
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'create_delivery') {
    global $pdo, $APPLICATION;
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');

    // Проверяем обязательные поля
    if (empty($_POST['date']) || empty($_POST['number'])) {
        echo json_encode(['success' => false, 'error' => 'Заполните обязательные поля: Дата и Номер заявки.']);
        die();
    }

    try {
        // Автоматически рассчитываем sort_order, чтобы заявка упала в конец списка
        $maxSort = $pdo->query("SELECT MAX(sort_order) FROM lanmark_logistic_orders")->fetchColumn();
        $nextSort = $maxSort ? intval($maxSort) + 10 : 10;

        $stmt = $pdo->prepare("INSERT INTO lanmark_logistic_orders (
            order_date, order_number, company_supplier, supplier_address, supplier_phone, supplier_comment, 
            company_client, client_address, client_phone, client_comment, order_label, sort_order, is_archive
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'N')");
        
        $success = $stmt->execute([
            trim($_POST['date']), 
            trim($_POST['number']),
            trim($_POST['company_supplier']), 
            trim($_POST['supplier_address']), 
            trim($_POST['supplier_phone']), 
            trim($_POST['supplier_comment']),
            trim($_POST['company_client']), 
            trim($_POST['client_address']), 
            trim($_POST['client_phone']), 
            trim($_POST['client_comment']),
            trim($_POST['label_status'] ?? 'NEW'),
            $nextSort
        ]);

        echo json_encode(['success' => $success]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    die();
}
?>
