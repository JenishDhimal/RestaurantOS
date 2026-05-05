<?php
function fetch_kitchen_orders(PDO $pdo): array {
    return $pdo->query(
        "SELECT o.id, o.type, o.table_number, o.status,
                GROUP_CONCAT(CONCAT(oi.quantity, '× ', mi.name) ORDER BY mi.name SEPARATOR ', ') AS items_summary,
                TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS elapsed_seconds
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN menu_items  mi ON mi.id = oi.menu_item_id
         WHERE o.status IN ('Received','Preparing','Ready')
         GROUP BY o.id
         ORDER BY o.created_at DESC"
    )->fetchAll();
}
