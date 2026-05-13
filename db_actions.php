<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// 1. ОБРАБОТКА ДЕЙСТВИЙ РАЗДЕЛА ПОСТАВЩИКОВ (CRUD)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supplier_action'])) {
    if ($_POST['supplier_action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO lanmark_logistic_suppliers (name, address, manager, phone, email, comment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            htmlspecialcharsbx($_POST['s_name']), htmlspecialcharsbx($_POST['s_address']), 
            htmlspecialcharsbx($_POST['s_manager']), htmlspecialcharsbx($_POST['s_phone']), 
            htmlspecialcharsbx($_POST['s_email']), htmlspecialcharsbx($_POST['s_comment'])
        ]);
    }
    if ($_POST['supplier_action'] == 'edit' && intval($_POST['s_id']) > 0) {
        $stmt = $pdo->prepare("UPDATE lanmark_logistic_suppliers SET name = ?, address = ?, manager = ?, phone = ?, email = ?, comment = ? WHERE id = ?");
        $stmt->execute([
            htmlspecialcharsbx($_POST['s_name']), htmlspecialcharsbx($_POST['s_address']), 
            htmlspecialcharsbx($_POST['s_manager']), htmlspecialcharsbx($_POST['s_phone']), 
            htmlspecialcharsbx($_POST['s_email']), htmlspecialcharsbx($_POST['s_comment']), 
            intval($_POST['s_id'])
        ]);
    }
    LocalRedirect($APPLICATION->GetCurPageParam("view_suppliers=Y", array("edit_supplier_id", "s_id")));
}

if (isset($_GET['delete_supplier_id']) && intval($_GET['delete_supplier_id']) > 0) {
    $stmt = $pdo->prepare("DELETE FROM lanmark_logistic_suppliers WHERE id = ?");
    $stmt->execute([intval($_GET['delete_supplier_id'])]);
    LocalRedirect($APPLICATION->GetCurPageParam("view_suppliers=Y", array("delete_supplier_id")));
}

// 2. ОБРАБОТКА СОРТИРОВКИ МАРШРУТНОГО ЛИСТА (ВВЕРХ / ВНИЗ)
if (isset($_GET['move_id']) && isset($_GET['dir'])) {
    $moveId = intval($_GET['move_id']);
    $direction = ($_GET['dir'] == 'up') ? -10 : 10;
    
    $stmt = $pdo->prepare("SELECT sort_order FROM lanmark_logistic_orders WHERE id = ?");
    $stmt->execute([$moveId]);
    $currentSort = $stmt->fetchColumn();
    
    $newSort = intval($currentSort) + $direction;
    if ($newSort < 10) $newSort = 10;
    
    $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET sort_order = ? WHERE id = ?");
    $stmt->execute([$newSort, $moveId]);
    LocalRedirect($APPLICATION->GetCurPageParam("view_archive=" . htmlspecialcharsbx($_GET['view_archive'] ?? 'N'), array("move_id", "dir")));
}

// 3. СТАНДАРТНОЕ ДОБАВЛЕНИЕ НОВОЙ ЗАЯВКИ ИЗ ФОРМЫ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_order') {
    $supplierId = intval($_POST['order_supplier_id']) > 0 ? intval($_POST['order_supplier_id']) : null;
    $stmt = $pdo->prepare("INSERT INTO lanmark_logistic_orders (order_date, order_number, supplier_id, company_supplier, supplier_address, supplier_phone, supplier_comment, company_client, client_address, client_phone, client_comment, order_label) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        htmlspecialcharsbx($_POST['date']), htmlspecialcharsbx($_POST['number']), $supplierId,
        htmlspecialcharsbx($_POST['company_supplier']), htmlspecialcharsbx($_POST['supplier_address']), htmlspecialcharsbx($_POST['supplier_phone']), htmlspecialcharsbx($_POST['supplier_comment']),
        htmlspecialcharsbx($_POST['company_client']), htmlspecialcharsbx($_POST['client_address']), htmlspecialcharsbx($_POST['client_phone']), htmlspecialcharsbx($_POST['client_comment']),
        htmlspecialcharsbx($_POST['label_status'])
    ]);
    LocalRedirect($APPLICATION->GetCurPage());
}

// 4. ЖУРНАЛ ЗАЯВОК: АРХИВАЦИЯ, ИЗВЛЕЧЕНИЕ И ПОЛНОЕ УДАЛЕНИЕ
if (isset($_GET['archive_id']) && intval($_GET['archive_id']) > 0) {
    $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET is_archive = 'Y' WHERE id = ?");
    $stmt->execute([intval($_GET['archive_id'])]);
    LocalRedirect($APPLICATION->GetCurPageParam("", array("archive_id")));
}
if (isset($_GET['unarchive_id']) && intval($_GET['unarchive_id']) > 0) {
    $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET is_archive = 'N' WHERE id = ?");
    $stmt->execute([intval($_GET['unarchive_id'])]);
    LocalRedirect($APPLICATION->GetCurPageParam("", array("unarchive_id")));
}
if (isset($_GET['delete_id']) && intval($_GET['delete_id']) > 0) {
    $stmt = $pdo->prepare("DELETE FROM lanmark_logistic_orders WHERE id = ?");
    $stmt->execute([intval($_GET['delete_id'])]);
    LocalRedirect($APPLICATION->GetCurPageParam("", array("delete_id")));
}

// 5. ПОДГОТОВКА И ВЫБОРКА МАССИВОВ ДАННЫХ ДЛЯ CRM_INTERFACE
$showSuppliersTab = (isset($_GET['view_suppliers']) && $_GET['view_suppliers'] == 'Y') ? true : false;

// Выборка справочника поставщиков (для рендеринга и селектов)
$stmt = $pdo->query("SELECT * FROM lanmark_logistic_suppliers ORDER BY name ASC");
$allSuppliers = $stmt->fetchAll();

$editSupplier = false;
if (isset($_GET['edit_supplier_id']) && intval($_GET['edit_supplier_id']) > 0) {
    foreach ($allSuppliers as $sup) { if ($sup['id'] == intval($_GET['edit_supplier_id'])) { $editSupplier = $sup; break; } }
}

// Выборка логистических заявок (текущие или архив)
$showArchive = (isset($_GET['view_archive']) && $_GET['view_archive'] == 'Y') ? 'Y' : 'N';
$stmt = $pdo->prepare("SELECT * FROM lanmark_logistic_orders WHERE is_archive = ? ORDER BY sort_order ASC, order_date DESC");
$stmt->execute([$showArchive]);
$orders = $stmt->fetchAll();
?>
