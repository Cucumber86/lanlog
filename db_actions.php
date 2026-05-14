<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// Явно указываем глобальный контекст Битрикс и PDO
global $pdo, $APPLICATION;

// =========================================================================
// 1. ОБРАБОТКА ДЕЙСТВИЙ РАЗДЕЛА ПОСТАВЩИКОВ (CRUD)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supplier_action'])) {
    if ($_POST['supplier_action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO lanmark_logistic_suppliers (name, address, manager, phone, email, comment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['s_name']), trim($_POST['s_address']), 
            trim($_POST['s_manager']), trim($_POST['s_phone']), 
            trim($_POST['s_email']), trim($_POST['s_comment'])
        ]);
    }
    if ($_POST['supplier_action'] == 'edit' && intval($_POST['s_id']) > 0) {
        $stmt = $pdo->prepare("UPDATE lanmark_logistic_suppliers SET name = ?, address = ?, manager = ?, phone = ?, email = ?, comment = ? WHERE id = ?");
        $stmt->execute([
            trim($_POST['s_name']), trim($_POST['s_address']), 
            trim($_POST['s_manager']), trim($_POST['s_phone']), 
            trim($_POST['s_email']), trim($_POST['s_comment']), 
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

// =========================================================================
// 2. НАДЕЖНАЯ СОРТИРОВКА ПОМЕСТНЫМ ОБМЕНОМ
// =========================================================================
if (isset($_GET['move_id']) && isset($_GET['dir'])) {
    $moveId = intval($_GET['move_id']);
    $dir = ($_GET['dir'] == 'up') ? 'up' : 'down';
    
    $stmt = $pdo->prepare("SELECT order_date FROM lanmark_logistic_orders WHERE id = ?");
    $stmt->execute([$moveId]);
    $targetDate = $stmt->fetchColumn();
    
    if ($targetDate) {
        $stmt = $pdo->prepare("SELECT id, sort_order FROM lanmark_logistic_orders WHERE order_date = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$targetDate]);
        $dayOrders = $stmt->fetchAll();
        
        $currentIndex = -1;
        foreach ($dayOrders as $index => $row) {
            if (intval($row['id']) === $moveId) {
                $currentIndex = $index;
                break;
            }
        }
        
        $neighborIndex = ($dir == 'up') ? $currentIndex - 1 : $currentIndex + 1;
        
        if ($currentIndex !== -1 && isset($dayOrders[$neighborIndex])) {
            $currentOrderData = $dayOrders[$currentIndex];
            $neighborOrderData = $dayOrders[$neighborIndex];
            
            if (intval($currentOrderData['sort_order']) === intval($neighborOrderData['sort_order'])) {
                if ($dir == 'up') {
                    $newCurrentSort = intval($currentOrderData['sort_order']) - 5;
                    $newNeighborSort = intval($neighborOrderData['sort_order']) + 5;
                } else {
                    $newCurrentSort = intval($currentOrderData['sort_order']) + 5;
                    $newNeighborSort = intval($neighborOrderData['sort_order']) - 5;
                }
                
                $pdo->prepare("UPDATE lanmark_logistic_orders SET sort_order = ? WHERE id = ?")->execute([$newCurrentSort, $currentOrderData['id']]);
                $pdo->prepare("UPDATE lanmark_logistic_orders SET sort_order = ? WHERE id = ?")->execute([$newNeighborSort, $neighborOrderData['id']]);
            } else {
                $pdo->prepare("UPDATE lanmark_logistic_orders SET sort_order = ? WHERE id = ?")->execute([$neighborOrderData['sort_order'], $currentOrderData['id']]);
                $pdo->prepare("UPDATE lanmark_logistic_orders SET sort_order = ? WHERE id = ?")->execute([$currentOrderData['sort_order'], $neighborOrderData['id']]);
            }
        }
    }
    LocalRedirect($APPLICATION->GetCurPageParam("", array("move_id", "dir")));
}

// =========================================================================
// 3. ОБЪЕДИНЕННОЕ СОЗДАНИЕ И ИЗМЕНЕНИЕ ЗАЯВКИ ИЗ МОДИФИЦИРОВАННОЙ ФОРМЫ
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_order') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $supplierId = intval($_POST['order_supplier_id']) > 0 ? intval($_POST['order_supplier_id']) : null;
    $editClientOnly = ($_POST['edit_client_only'] ?? 'N') === 'Y';
    
    if ($orderId > 0) {
        if ($editClientOnly) {
            // ОБНОВЛЕНИЕ ТОЛЬКО КЛИЕНТА (Контактное лицо покупателя)
            $stmt = $pdo->prepare("SELECT order_date, client_address FROM lanmark_logistic_orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $currentData = $stmt->fetch();
            
            if ($currentData) {
                // Приводим ключи к нижнему регистру на случай если СУБД вернула их в верхнем
                $currentData = array_change_key_case($currentData, CASE_LOWER);
                
                $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET company_client = ?, client_address = ?, client_phone = ?, client_manager = ?, client_comment = ? WHERE order_date = ? AND LOWER(TRIM(client_address)) = LOWER(TRIM(?))");
                $stmt->execute([
                    trim($_POST['company_client']), trim($_POST['client_address']), trim($_POST['client_phone']), trim($_POST['client_manager']), trim($_POST['client_comment']),
                    $currentData['order_date'], $currentData['client_address']
                ]);
            }
        } else {
            // ПОЛНОЕ РЕДАКТИРОВАНИЕ СТРОКИ СЧЕТА
            $stmt = $pdo->prepare("UPDATE lanmark_logistic_orders SET order_date = ?, order_number = ?, supplier_id = ?, company_supplier = ?, supplier_address = ?, supplier_phone = ?, supplier_manager = ?, supplier_invoice = ?, supplier_comment = ?, company_client = ?, client_address = ?, client_phone = ?, client_manager = ?, client_comment = ? WHERE id = ?");
            $stmt->execute([
                trim($_POST['date']), trim($_POST['number']), $supplierId,
                trim($_POST['company_supplier']), trim($_POST['supplier_address']), trim($_POST['supplier_phone']), trim($_POST['supplier_manager']), trim($_POST['supplier_invoice']), trim($_POST['supplier_comment']),
                trim($_POST['company_client']), trim($_POST['client_address']), trim($_POST['client_phone']), trim($_POST['client_manager']), trim($_POST['client_comment']),
                $orderId
            ]);
        }
    } else {
        // СОЗДАНИЕ ЗАЯВКИ
        $maxSort = $pdo->query("SELECT MAX(sort_order) FROM lanmark_logistic_orders")->fetchColumn();
        $nextSort = $maxSort ? intval($maxSort) + 10 : 10;
        
        $stmt = $pdo->prepare("INSERT INTO lanmark_logistic_orders (order_date, order_number, supplier_id, company_supplier, supplier_address, supplier_phone, supplier_manager, supplier_invoice, supplier_comment, company_client, client_address, client_phone, client_manager, client_comment, order_label, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'NEW', ?)");
        $stmt->execute([
            trim($_POST['date']), trim($_POST['number']), $supplierId,
            trim($_POST['company_supplier']), trim($_POST['supplier_address']), trim($_POST['supplier_phone']), trim($_POST['supplier_manager']), trim($_POST['supplier_invoice']), trim($_POST['supplier_comment']),
            trim($_POST['company_client']), trim($_POST['client_address']), trim($_POST['client_phone']), trim($_POST['client_manager']), trim($_POST['client_comment']),
            $nextSort
        ]);
    }
    LocalRedirect($APPLICATION->GetCurPage());
}

// =========================================================================
// 4. ПОЛНОЕ БЕЗВОЗВРАТНОЕ УДАЛЕНИЕ ЗАЯВКИ ИЛИ ВСЕЙ ГРУППЫ КЛИЕНТА
// =========================================================================
if (isset($_GET['delete_id']) && intval($_GET['delete_id']) > 0) {
    $stmt = $pdo->prepare("DELETE FROM lanmark_logistic_orders WHERE id = ?");
    $stmt->execute([intval($_GET['delete_id'])]);
    LocalRedirect($APPLICATION->GetCurPageParam("", array("delete_id")));
}

if (isset($_GET['delete_client_group_id']) && intval($_GET['delete_client_group_id']) > 0) {
    $baseId = intval($_GET['delete_client_group_id']);
    $stmt = $pdo->prepare("SELECT order_date, client_address FROM lanmark_logistic_orders WHERE id = ?");
    $stmt->execute([$baseId]);
    $groupData = $stmt->fetch();
    
    if ($groupData) {
        $groupData = array_change_key_case($groupData, CASE_LOWER);
        $stmt = $pdo->prepare("DELETE FROM lanmark_logistic_orders WHERE order_date = ? AND LOWER(TRIM(client_address)) = LOWER(TRIM(?))");
        $stmt->execute([$groupData['order_date'], $groupData['client_address']]);
    }
    LocalRedirect($APPLICATION->GetCurPageParam("", array("delete_client_group_id")));
}

// =========================================================================
// 5. ПОДГОТОВКА И СТАБИЛЬНАЯ ВЫБОРКА МАССИВОВ С ПРИВЕДЕНИЕМ К НИЖНЕМУ РЕГИСТРУ
// =========================================================================
$showSuppliersTab = (isset($_GET['view_suppliers']) && $_GET['view_suppliers'] == 'Y');

// Выборка справочника поставщиков
$stmt = $pdo->query("SELECT * FROM lanmark_logistic_suppliers ORDER BY name ASC");
$rawSuppliers = $stmt->fetchAll();
$allSuppliers = [];
foreach ($rawSuppliers as $sup) {
    $allSuppliers[] = array_change_key_case($sup, CASE_LOWER);
}

$editSupplier = false;
if (isset($_GET['edit_supplier_id']) && intval($_GET['edit_supplier_id']) > 0) {
    $targetId = intval($_GET['edit_supplier_id']);
    foreach ($allSuppliers as $sup) { 
        if (intval($sup['id']) === $targetId) { 
            $editSupplier = $sup; 
            break; 
        } 
    }
}

// ИСПРАВЛЕНО: Выборка всех заявок с принудительным приведением ключей к нижнему регистру
$stmt = $pdo->query("SELECT * FROM lanmark_logistic_orders ORDER BY order_date DESC, sort_order ASC");
$rawOrders = $stmt->fetchAll();
$orders = [];
foreach ($rawOrders as $ord) {
    $orders[] = array_change_key_case($ord, CASE_LOWER);
}
?>
