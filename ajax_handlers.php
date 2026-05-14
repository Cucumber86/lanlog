<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// Явно объявляем глобальные переменные для работы с Битрикс и PDO
global $pdo, $APPLICATION;

// 1. ФОНОВЫЙ AJAX-ОБРАБОТЧИК ДЛЯ СМЕНЫ ЯРЛЫКОВ «НА ЛЕТУ»
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'set_label') {
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');
    
    $elementID = intval($_GET['element_id']);
    $labelXmlId = trim($_GET['set_label']);
    
    if ($elementID <= 0) {
        echo json_encode(['success' => false, 'error' => 'Неверный ID элемента']);
        die();
    }
    
    $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET order_label = ? WHERE id = ?");
    $success = $stmt->execute([$labelXmlId, $elementID]);
    
    echo json_encode(['success' => $success, 'label' => htmlspecialcharsbx($labelXmlId)]);
    die();
}

// 2. ФОНОВЫЙ AJAX-ОБРАБОТЧИК ДЛЯ СОЗДАНИЯ НОВОЙ ЗАЯВКИ ИЗ МОДАЛЬНОГО ОКНА (ОСТАВЛЕН ДЛЯ СОВМЕСТИМОСТИ)
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'create_delivery') {
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');

    if (empty($_POST['date']) || empty($_POST['number'])) {
        echo json_encode(['success' => false, 'error' => 'Заполните обязательные поля: Дата и Номер заявки.']);
        die();
    }

    try {
        $maxSort = $pdo->query("SELECT MAX(sort_order) FROM lanmark_logistic_orders")->fetchColumn();
        $nextSort = $maxSort ? intval($maxSort) + 10 : 10;

        $stmt = $pdo->prepare("INSERT INTO lanmark_logistic_orders (
            order_date, order_number, company_supplier, supplier_address, supplier_phone, supplier_comment, 
            company_client, client_address, client_phone, client_comment, order_label, sort_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'NEW', ?)");
        
        $success = $stmt->execute([
            trim($_POST['date']), trim($_POST['number']),
            trim($_POST['company_supplier']), trim($_POST['supplier_address']), trim($_POST['supplier_phone']), trim($_POST['supplier_comment']),
            trim($_POST['company_client']), trim($_POST['client_address']), trim($_POST['client_phone']), trim($_POST['client_comment']),
            $nextSort
        ]);

        echo json_encode(['success' => $success]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    die();
}

// 3. AJAX-ОБРАБОТЧИК ДЛЯ ПОЛУЧЕНИЯ ДАННЫХ ПОСТАВЩИКА ПРИ ВЫБОРЕ ИЗ СПИСКА
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'get_supplier_info') {
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');
    
    $supplierId = intval($_GET['supplier_id']);
    if ($supplierId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Неверный ID контрагента']);
        die();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM lanmark_logistic_suppliers WHERE id = ?");
    $stmt->execute([$supplierId]);
    $supplierData = $stmt->fetch();
    
    if ($supplierData) {
        echo json_encode([
            'success' => true, 
            'name' => htmlspecialcharsbx($supplierData['name']),
            'address' => htmlspecialcharsbx($supplierData['address']),
            'phone' => htmlspecialcharsbx($supplierData['phone']),
            'manager' => htmlspecialcharsbx($supplierData['manager']), // ДОБАВЛЕНО: передаем ФИО Менеджера из справочника
            'comment' => htmlspecialcharsbx($supplierData['comment'])
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Поставщик не найден']);
    }
    die();
}
?>
