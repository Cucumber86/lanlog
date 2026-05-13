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
?>
