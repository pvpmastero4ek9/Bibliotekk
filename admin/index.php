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
        $toast = 'Статус заявки #' . $applicationId . ' изменён на «' . $status . '»';
    }
}

$courses = $db->query('SELECT id, name FROM courses ORDER BY id')->fetchAll();
$statusFilter = $_GET['status'] ?? '';
$courseFilter = (string)($_GET['course_id'] ?? '');
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

$validCourseIds = array_column($courses, 'id');
if ($courseFilter !== '' && in_array((int)$courseFilter, $validCourseIds, true)) {
    $where .= ' AND a.course_id = ?';
    $params[] = (int)$courseFilter;
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
    'course_id' => $courseFilter,
    'sort' => $sort,
    'order' => $order,
];

$pageTitle = 'Управление заявками — Учусь.РФ';
$bodyClass = 'page-admin';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card admin-card slide-up">
    <div class="cabinet-head">
        <div class="cabinet-intro">
            <div class="card-head">
                <h2>Заявки пользователей</h2>
            </div>
            <p class="subtitle">Всего: <?= $total ?></p>
        </div>
        <a href="/admin/logout.php" class="btn btn-secondary">Выход</a>
    </div>

    <form method="get" class="admin-filters">
        <div class="field">
            <label for="status">Статус</label>
            <select id="status" name="status">
                <option value="">Все</option>
                <?php foreach (['Новая', 'Идет обучение', 'Обучение завершено'] as $statusOption): ?>
                    <option value="<?= escape($statusOption) ?>" <?= $statusFilter === $statusOption ? 'selected' : '' ?>>
                        <?= escape($statusOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="course_id">Курс</label>
            <select id="course_id" name="course_id">
                <option value="">Все</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= (int)$course['id'] ?>" <?= $courseFilter === (string)$course['id'] ? 'selected' : '' ?>>
                        <?= escape($course['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="sort">Сортировка</label>
            <select id="sort" name="sort">
                <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>По дате создания</option>
                <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>По статусу</option>
                <option value="course" <?= $sort === 'course' ? 'selected' : '' ?>>По курсу</option>
                <option value="user" <?= $sort === 'user' ? 'selected' : '' ?>>По пользователю</option>
            </select>
        </div>
        <div class="field">
            <label for="order">Порядок</label>
            <select id="order" name="order">
                <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>По убыванию</option>
                <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>По возрастанию</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Применить</button>
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
                                <span class="muted"><?= escape($app['login']) ?></span><br>
                                <span class="muted"><?= escape($app['phone']) ?></span>
                            </td>
                            <td><?= escape($app['course_name']) ?></td>
                            <td><?= escape(dateFromStorage($app['start_date'])) ?></td>
                            <td><?= escape($app['payment_method']) ?></td>
                            <td>
                                <span class="status-badge status-<?= escape(statusClassSlug($app['status'])) ?>">
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
                <?php if ($page > 1): ?>
                    <?php $prevQuery = array_merge($queryBase, ['page' => $page - 1]); ?>
                    <a class="page-link" href="?<?= http_build_query($prevQuery) ?>" aria-label="Предыдущая страница">&#10094;</a>
                <?php endif; ?>
                <span class="pagination-info">Страница <?= $page ?> из <?= $totalPages ?></span>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $query = array_merge($queryBase, ['page' => $i]);
                    $active = $i === $page ? ' active' : '';
                    ?>
                    <a class="page-link<?= $active ?>" href="?<?= http_build_query($query) ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <?php $nextQuery = array_merge($queryBase, ['page' => $page + 1]); ?>
                    <a class="page-link" href="?<?= http_build_query($nextQuery) ?>" aria-label="Следующая страница">&#10095;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($toast): ?>
    <div class="toast show" data-toast><?= escape($toast) ?></div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
