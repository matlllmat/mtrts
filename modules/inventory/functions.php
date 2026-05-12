<?php
// modules/inventory/functions.php — Parts inventory DB layer.

function list_parts(PDO $pdo, array $f, int $page, int $per_page): array {
    $where  = ['p.is_active = 1'];
    $params = [];

    if ($f['q'] ?? '') {
        $where[] = "(p.part_name LIKE ? OR p.part_number LIKE ? OR p.manufacturer LIKE ?)";
        $like = '%' . $f['q'] . '%';
        array_push($params, $like, $like, $like);
    }
    if ($f['category'] ?? '') {
        $where[] = "p.category = ?";
        $params[] = $f['category'];
    }
    if (($f['stock'] ?? '') === 'low') {
        $where[] = "p.quantity_on_hand <= p.reorder_level AND p.quantity_on_hand > 0";
    } elseif (($f['stock'] ?? '') === 'out') {
        $where[] = "p.quantity_on_hand <= 0";
    } elseif (($f['stock'] ?? '') === 'ok') {
        $where[] = "p.quantity_on_hand > p.reorder_level";
    }

    $sort_cols = ['part_name','part_number','category','quantity_on_hand','reorder_level','unit_cost','updated_at'];
    $sort_col  = in_array($f['sort_col'] ?? '', $sort_cols, true) ? $f['sort_col'] : 'part_name';
    $sort_dir  = strtoupper($f['sort_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    $offset = ($page - 1) * $per_page;
    $sql = "SELECT p.* FROM parts_inventory p
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.{$sort_col} {$sort_dir}, p.part_id ASC
            LIMIT {$per_page} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_parts(PDO $pdo, array $f): int {
    $where  = ['p.is_active = 1'];
    $params = [];
    if ($f['q'] ?? '') {
        $where[] = "(p.part_name LIKE ? OR p.part_number LIKE ? OR p.manufacturer LIKE ?)";
        $like = '%' . $f['q'] . '%';
        array_push($params, $like, $like, $like);
    }
    if ($f['category'] ?? '') { $where[] = "p.category = ?"; $params[] = $f['category']; }
    if (($f['stock'] ?? '') === 'low') $where[] = "p.quantity_on_hand <= p.reorder_level AND p.quantity_on_hand > 0";
    elseif (($f['stock'] ?? '') === 'out') $where[] = "p.quantity_on_hand <= 0";
    elseif (($f['stock'] ?? '') === 'ok')  $where[] = "p.quantity_on_hand > p.reorder_level";
    $sql = "SELECT COUNT(*) FROM parts_inventory p WHERE " . implode(' AND ', $where);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_inventory_stats(PDO $pdo): array {
    $row = $pdo->query("
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN quantity_on_hand <= 0 THEN 1 ELSE 0 END)                                AS out_of_stock,
          SUM(CASE WHEN quantity_on_hand > 0 AND quantity_on_hand <= reorder_level THEN 1 ELSE 0 END) AS low_stock,
          SUM(CASE WHEN quantity_on_hand > reorder_level THEN 1 ELSE 0 END)                    AS ok_stock,
          COALESCE(SUM(quantity_on_hand * COALESCE(unit_cost, 0)), 0)                          AS total_value
        FROM parts_inventory
        WHERE is_active = 1
    ")->fetch(PDO::FETCH_ASSOC) ?: [];
    return [
        'total'        => (int)($row['total'] ?? 0),
        'out_of_stock' => (int)($row['out_of_stock'] ?? 0),
        'low_stock'    => (int)($row['low_stock'] ?? 0),
        'ok_stock'     => (int)($row['ok_stock'] ?? 0),
        'total_value'  => (float)($row['total_value'] ?? 0),
    ];
}

function get_part(PDO $pdo, int $part_id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM parts_inventory WHERE part_id = ? LIMIT 1");
    $stmt->execute([$part_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function get_part_categories(PDO $pdo): array {
    $rows = $pdo->query("
        SELECT category, COUNT(*) AS n
        FROM parts_inventory
        WHERE is_active = 1 AND category IS NOT NULL AND category <> ''
        GROUP BY category
        ORDER BY category ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    return $rows ?: [];
}

function save_part(PDO $pdo, array $data, ?int $part_id = null, ?int $user_id = null): int {
    $fields = [
        'part_number'      => trim($data['part_number'] ?? ''),
        'part_name'        => trim($data['part_name'] ?? ''),
        'description'      => trim($data['description'] ?? '') ?: null,
        'manufacturer'     => trim($data['manufacturer'] ?? '') ?: null,
        'category'         => trim($data['category'] ?? '') ?: null,
        'compatible_with'  => trim($data['compatible_with'] ?? '') ?: null,
        'reorder_level'    => max(0, (int)($data['reorder_level'] ?? 5)),
        'unit_cost'        => isset($data['unit_cost']) && $data['unit_cost'] !== '' ? (float)$data['unit_cost'] : null,
        'unit_price'       => isset($data['unit_price']) && $data['unit_price'] !== '' ? (float)$data['unit_price'] : null,
        'storage_location' => trim($data['storage_location'] ?? '') ?: null,
    ];
    if ($fields['part_number'] === '' || $fields['part_name'] === '') {
        throw new InvalidArgumentException('Part number and part name are required.');
    }

    if ($part_id) {
        $sql = "UPDATE parts_inventory SET part_number=?, part_name=?, description=?, manufacturer=?, category=?,
                compatible_with=?, reorder_level=?, unit_cost=?, unit_price=?, storage_location=? WHERE part_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($fields), [$part_id]));
        return $part_id;
    }

    $qty = max(0, (int)($data['initial_qty'] ?? 0));
    $sql = "INSERT INTO parts_inventory
            (part_number, part_name, description, manufacturer, category, compatible_with,
             quantity_on_hand, reorder_level, unit_cost, unit_price, storage_location, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $fields['part_number'], $fields['part_name'], $fields['description'], $fields['manufacturer'],
        $fields['category'], $fields['compatible_with'], $qty, $fields['reorder_level'],
        $fields['unit_cost'], $fields['unit_price'], $fields['storage_location'],
    ]);
    $new_id = (int)$pdo->lastInsertId();

    if ($qty > 0) {
        $pdo->prepare("INSERT INTO parts_inventory_audit
            (part_id, change_type, action, quantity_change, new_quantity, reason, changed_by)
            VALUES (?, 'initial', 'initial', ?, ?, 'Initial stock on part creation', ?)"
        )->execute([$new_id, $qty, $qty, $user_id]);
    }
    return $new_id;
}

function soft_delete_part(PDO $pdo, int $part_id): void {
    $pdo->prepare("UPDATE parts_inventory SET is_active = 0 WHERE part_id = ?")->execute([$part_id]);
}

function adjust_stock(PDO $pdo, int $part_id, int $delta, string $reason, int $user_id): array {
    if ($delta === 0) {
        throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
    }
    $pdo->beginTransaction();
    try {
        $cur = $pdo->prepare("SELECT quantity_on_hand, reorder_level FROM parts_inventory WHERE part_id = ? FOR UPDATE");
        $cur->execute([$part_id]);
        $row = $cur->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('Part not found.');

        $new_qty = max(0, (int)$row['quantity_on_hand'] + $delta);
        $reorder = (int)$row['reorder_level'];

        $pdo->prepare("UPDATE parts_inventory SET quantity_on_hand = ? WHERE part_id = ?")
            ->execute([$new_qty, $part_id]);

        $change_type = $delta > 0 ? 'add' : 'subtract';
        $pdo->prepare("INSERT INTO parts_inventory_audit
            (part_id, change_type, action, quantity_change, new_quantity, reason, changed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$part_id, $change_type, $change_type, $delta, $new_qty, $reason ?: null, $user_id]);

        if ($new_qty <= $reorder) {
            $alert_type = $new_qty <= 0 ? 'out_of_stock' : 'low_stock';
            $pdo->prepare("INSERT INTO parts_low_stock_alerts (part_id, alert_type, threshold, current_stock)
                           VALUES (?, ?, ?, ?)"
            )->execute([$part_id, $alert_type, $reorder, $new_qty]);
        }

        $pdo->commit();
        return ['new_qty' => $new_qty];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function get_part_audit_history(PDO $pdo, int $part_id, int $limit = 25): array {
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name AS user_name
        FROM parts_inventory_audit a
        LEFT JOIN users u ON a.changed_by = u.user_id
        WHERE a.part_id = ?
        ORDER BY a.changed_at DESC
        LIMIT {$limit}
    ");
    $stmt->execute([$part_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_low_stock_alerts(PDO $pdo, bool $unack_only = true): array {
    $where = $unack_only ? "WHERE a.is_acknowledged = 0" : "";
    $stmt  = $pdo->query("
        SELECT a.*, p.part_name, p.part_number, p.quantity_on_hand AS current_on_hand, p.reorder_level,
               u.full_name AS ack_by_name
        FROM parts_low_stock_alerts a
        JOIN parts_inventory p ON a.part_id = p.part_id
        LEFT JOIN users u ON a.acknowledged_by = u.user_id
        {$where}
        ORDER BY a.created_at DESC
        LIMIT 100
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function acknowledge_alert(PDO $pdo, int $alert_id, int $user_id): void {
    $pdo->prepare("UPDATE parts_low_stock_alerts
                   SET is_acknowledged = 1, acknowledged_by = ?, acknowledged_at = NOW()
                   WHERE alert_id = ? AND is_acknowledged = 0"
    )->execute([$user_id, $alert_id]);
}

function count_unacknowledged_alerts(PDO $pdo): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM parts_low_stock_alerts WHERE is_acknowledged = 0")->fetchColumn();
}
