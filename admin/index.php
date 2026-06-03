<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$db = Database::getConnection();
$toast = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
    $applicationId = (int)$_POST['application_id'];
    $status = $_POST['status'];
    $allowed = ['Новая', 'Идет обучение', 'Обучение завершено'];
    if (in_array($status, $allowed, true)) {
        $stmt = $db->prepare('UPDATE applications SET status = ? WHERE id = ?');
        $stmt->execute([$status, $applicationId]);
        $toast = 'Статус заявки #' . $applicationId . ' обновлен';
    }
}

$statusFilter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;

$allowedSort = [
    'created_at' => 'a.created_at',
    'status' => 'a.status',
    'course' => 'c.name',
    'user' => 'u.full_name',
];
$sortColumn = $allowedSort[$sort] ?? $allowedSort['created_at'];

$where = 'WHERE 1=1';
$params = [];
if ($statusFilter !== '' && in_array($statusFilter, ['Новая', 'Идет обучение', 'Обучение завершено'], true)) {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
}

$countSql = "SELECT COUNT(*) FROM applications a JOIN users u ON u.id = a.user_id JOIN courses c ON c.id = a.course_id $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT a.id, a.start_date, a.payment_method, a.status, a.created_at,
           u.full_name, u.login, u.phone, u.email, c.name AS course_name
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN courses c ON c.id = a.course_id
    $where
    ORDER BY $sortColumn $order
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

function sortLink(string $field, string $label, string $currentSort, string $currentOrder, array $query): string
{
    $query['sort'] = $field;
    $query['order'] = ($currentSort === $field && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($currentSort === $field) {
        $arrow = $currentOrder === 'ASC' ? ' ↑' : ' ↓';
    }
    return '<a class="sort-link" href="?' . http_build_query($query) . '">' . escape($label) . $arrow . '</a>';
}

$queryBase = [
    'status' => $statusFilter,
    'sort' => $sort,
    'order' => $order,
];

$pageTitle = 'Управление заявками — Учусь.РФ';
$bodyClass = 'page-admin';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card admin-card slide-up">
    <div class="cabinet-head">
        <div>
            <h2>Заявки пользователей</h2>
            <p class="subtitle">Всего: <?= $total ?></p>
        </div>
        <a href="/admin/logout.php" class="btn btn-secondary">Выход</a>
    </div>

    <form method="get" class="admin-filters">
        <div class="field">
            <label for="status">Фильтр по статусу</label>
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="">Все статусы</option>
                <?php foreach (['Новая', 'Идет обучение', 'Обучение завершено'] as $statusOption): ?>
                    <option value="<?= escape($statusOption) ?>" <?= $statusFilter === $statusOption ? 'selected' : '' ?>>
                        <?= escape($statusOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="sort" value="<?= escape($sort) ?>">
        <input type="hidden" name="order" value="<?= escape($order) ?>">
    </form>

    <?php if (!$applications): ?>
        <p class="empty-state">Заявки не найдены</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= sortLink('user', 'Пользователь', $sort, $order, $queryBase) ?></th>
                        <th><?= sortLink('course', 'Курс', $sort, $order, $queryBase) ?></th>
                        <th>Дата начала</th>
                        <th>Оплата</th>
                        <th><?= sortLink('status', 'Статус', $sort, $order, $queryBase) ?></th>
                        <th><?= sortLink('created_at', 'Создана', $sort, $order, $queryBase) ?></th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>#<?= (int)$app['id'] ?></td>
                            <td>
                                <strong><?= escape($app['full_name']) ?></strong><br>
                                <span class="muted"><?= escape($app['login']) ?></span>
                            </td>
                            <td><?= escape($app['course_name']) ?></td>
                            <td><?= escape(dateFromStorage($app['start_date'])) ?></td>
                            <td><?= escape($app['payment_method']) ?></td>
                            <td>
                                <span class="status-badge status-<?= escape(preg_replace('/\s+/', '-', mb_strtolower($app['status']))) ?>">
                                    <?= escape($app['status']) ?>
                                </span>
                            </td>
                            <td><?= escape(date('d.m.Y H:i', strtotime($app['created_at']))) ?></td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                    <select name="status" class="status-select">
                                        <?php foreach (['Новая', 'Идет обучение', 'Обучение завершено'] as $statusOption): ?>
                                            <option value="<?= escape($statusOption) ?>" <?= $app['status'] === $statusOption ? 'selected' : '' ?>>
                                                <?= escape($statusOption) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $query = array_merge($queryBase, ['page' => $i]);
                    $active = $i === $page ? ' active' : '';
                    ?>
                    <a class="page-link<?= $active ?>" href="?<?= http_build_query($query) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($toast): ?>
    <div class="toast show" data-toast><?= escape($toast) ?></div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
