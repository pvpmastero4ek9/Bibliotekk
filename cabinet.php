<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireUser();

$db = Database::getConnection();
$userId = currentUserId();
$reviewErrors = [];
$reviewSuccess = '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$userStmt = $db->prepare('SELECT full_name FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_application_id'])) {
    $applicationId = (int)$_POST['review_application_id'];
    $rating = (int)($_POST['rating'] ?? 0);
    $text = trim($_POST['text'] ?? '');

    $appStmt = $db->prepare('SELECT status FROM applications WHERE id = ? AND user_id = ?');
    $appStmt->execute([$applicationId, $userId]);
    $application = $appStmt->fetch();

    if (!$application) {
        $reviewErrors['general'] = 'Заявка не найдена';
    } elseif ($application['status'] === 'Новая') {
        $reviewErrors['general'] = 'Отзыв можно оставить после изменения статуса администратором';
    } elseif ($rating < 1 || $rating > 5) {
        $reviewErrors['general'] = 'Выберите оценку от 1 до 5';
    } elseif (utf8Length($text) < 10) {
        $reviewErrors['general'] = 'Текст отзыва должен содержать не менее 10 символов';
    } else {
        $exists = $db->prepare('SELECT id FROM reviews WHERE application_id = ?');
        $exists->execute([$applicationId]);
        if ($exists->fetch()) {
            $reviewErrors['general'] = 'Отзыв по этой заявке уже оставлен';
        } else {
            $insert = $db->prepare('INSERT INTO reviews (application_id, user_id, rating, text) VALUES (?, ?, ?, ?)');
            $insert->execute([$applicationId, $userId, $rating, $text]);
            $reviewSuccess = 'Отзыв успешно отправлен';
        }
    }
}

$appsStmt = $db->prepare("
    SELECT a.id, a.start_date, a.payment_method, a.status, a.created_at, c.name AS course_name,
           r.rating, r.text AS review_text
    FROM applications a
    JOIN courses c ON c.id = a.course_id
    LEFT JOIN reviews r ON r.application_id = a.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$appsStmt->execute([$userId]);
$applications = $appsStmt->fetchAll();

$pageTitle = 'Личный кабинет — Учусь.РФ';
$bodyClass = 'page-cabinet';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-banner fade-in">
    <img src="/assets/hero.png" alt="Обучение онлайн" class="hero-image">
</section>

<section class="slider-section slide-up" data-slider>
    <div class="slider">
        <div class="slider-track">
            <div class="slide active"><img src="/images/image%20(1).jpeg" alt="Курс 1"></div>
            <div class="slide"><img src="/images/image%20(2).jpeg" alt="Курс 2"></div>
            <div class="slide"><img src="/images/image%20(3).jpeg" alt="Курс 3"></div>
            <div class="slide"><img src="/images/image%20(4).jpeg" alt="Курс 4"></div>
        </div>
        <button type="button" class="slider-btn slider-prev" aria-label="Назад">&#10094;</button>
        <button type="button" class="slider-btn slider-next" aria-label="Вперед">&#10095;</button>
        <div class="slider-dots"></div>
    </div>
</section>

<section class="card cabinet-card slide-up">
    <div class="cabinet-head">
        <div>
            <h2>Личный кабинет</h2>
            <p class="subtitle">Здравствуйте, <?= escape($user['full_name']) ?></p>
        </div>
        <div class="cabinet-actions">
            <a href="/application.php" class="btn btn-primary">Новая заявка</a>
            <a href="/logout.php" class="btn btn-secondary">Выход</a>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= escape($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($reviewSuccess): ?>
        <div class="alert alert-success"><?= escape($reviewSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($reviewErrors['general'])): ?>
        <div class="alert alert-error"><?= escape($reviewErrors['general']) ?></div>
    <?php endif; ?>

    <h3>История заявок</h3>
    <?php if (!$applications): ?>
        <p class="empty-state">У вас пока нет заявок. <a href="/application.php">Оформите первую заявку</a></p>
    <?php else: ?>
        <div class="applications-list">
            <?php foreach ($applications as $app): ?>
                <article class="application-item">
                    <div class="application-top">
                        <strong><?= escape($app['course_name']) ?></strong>
                        <span class="status-badge status-<?= escape(statusClassSlug($app['status'])) ?>"><?= escape($app['status']) ?></span>
                    </div>
                    <p>Дата начала: <?= escape(dateFromStorage($app['start_date'])) ?></p>
                    <p>Оплата: <?= escape($app['payment_method']) ?></p>
                    <p class="muted">Создана: <?= escape(date('d.m.Y H:i', strtotime($app['created_at']))) ?></p>

                    <?php if ($app['review_text']): ?>
                        <div class="review-block">
                            <strong>Ваш отзыв:</strong>
                            <div class="stars"><?= str_repeat('★', (int)$app['rating']) . str_repeat('☆', 5 - (int)$app['rating']) ?></div>
                            <p><?= escape($app['review_text']) ?></p>
                        </div>
                    <?php elseif ($app['status'] !== 'Новая'): ?>
                        <form method="post" class="review-form">
                            <input type="hidden" name="review_application_id" value="<?= (int)$app['id'] ?>">
                            <div class="field">
                                <label>Оценка</label>
                                <select name="rating" required>
                                    <option value="">Выберите</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>"><?= $i ?> ★</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>Отзыв</label>
                                <textarea name="text" rows="3" required placeholder="Поделитесь впечатлениями"></textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-sm">Оставить отзыв</button>
                        </form>
                    <?php else: ?>
                        <p class="hint">Отзыв будет доступен после проверки заявки администратором</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
