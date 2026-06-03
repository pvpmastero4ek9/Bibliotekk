<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireUser();

$db = Database::getConnection();
$userId = currentUserId();
$errors = [];
$values = [
    'course_id' => '',
    'start_date' => '',
    'payment_method' => '',
];

$courses = $db->query('SELECT id, name FROM courses ORDER BY id')->fetchAll();
$paymentMethods = [
    'Предоплата по QR-коду',
    'Оплата картой МИР',
    'Постоплата в офисе организации',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['course_id'] = (string)($_POST['course_id'] ?? '');
    $values['start_date'] = trim($_POST['start_date'] ?? '');
    $values['payment_method'] = trim($_POST['payment_method'] ?? '');

    $validCourseIds = array_column($courses, 'id');
    if (!in_array((int)$values['course_id'], $validCourseIds, true)) {
        $errors['course_id'] = 'Выберите курс из списка';
    }

    $dateError = validateDate($values['start_date']);
    if ($dateError) {
        $errors['start_date'] = $dateError;
    }

    if (!in_array($values['payment_method'], $paymentMethods, true)) {
        $errors['payment_method'] = 'Выберите способ оплаты';
    }

    if (!$errors) {
        $stmt = $db->prepare('INSERT INTO applications (user_id, course_id, start_date, payment_method, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            (int)$values['course_id'],
            dateToStorage($values['start_date']),
            $values['payment_method'],
            'Новая',
        ]);
        $_SESSION['flash_success'] = 'Заявка отправлена на согласование администратору';
        header('Location: /cabinet.php');
        exit;
    }
}

$pageTitle = 'Оформление заявки — Учусь.РФ';
$bodyClass = 'page-application';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card slide-up">
    <div class="card-head">
        <h2>Оформление заявки</h2>
    </div>
    <p class="subtitle">Выберите курс, дату начала и способ оплаты</p>
    <form method="post" class="form">
        <div class="field">
            <label for="course_id">Курс</label>
            <select id="course_id" name="course_id" required>
                <option value="">Выберите курс</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= (int)$course['id'] ?>" <?= $values['course_id'] === (string)$course['id'] ? 'selected' : '' ?>>
                        <?= escape($course['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['course_id'])): ?><span class="field-error"><?= escape($errors['course_id']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="start_date">Дата начала обучения</label>
            <input type="text" id="start_date" name="start_date" value="<?= escape($values['start_date']) ?>" placeholder="ДД.ММ.ГГГГ" maxlength="10">
            <?php if (!empty($errors['start_date'])): ?><span class="field-error"><?= escape($errors['start_date']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="payment_method">Способ оплаты</label>
            <select id="payment_method" name="payment_method" required>
                <option value="">Выберите способ оплаты</option>
                <?php foreach ($paymentMethods as $method): ?>
                    <option value="<?= escape($method) ?>" <?= $values['payment_method'] === $method ? 'selected' : '' ?>>
                        <?= escape($method) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['payment_method'])): ?><span class="field-error"><?= escape($errors['payment_method']) ?></span><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Отправить заявку</button>
        <a href="/cabinet.php" class="btn btn-secondary btn-block">Назад в кабинет</a>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
