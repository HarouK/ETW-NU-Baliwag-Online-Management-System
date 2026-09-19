<?php
require 'config.php';
require_role('admin');

$title = 'Important Dates';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_date') {
            $eventTitle = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $eventDate = $_POST['event_date'] ?? '';
            $eventType = $_POST['event_type'] ?? 'Academic';

            if ($eventTitle === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
                throw new Exception('Title and a valid date are required.');
            }
            if (!in_array($eventType, ['Academic', 'Enrollment', 'Exam', 'Holiday', 'Other'], true)) {
                $eventType = 'Academic';
            }

            $stmt = $pdo->prepare(
                'INSERT INTO important_dates (title, description, event_date, event_type, created_by) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $eventTitle,
                $description,
                $eventDate,
                $eventType,
                (int) $_SESSION['user']['id']
            ]);

            $_SESSION['admin_dates_flash'] = ['msg' => 'Important date added successfully.', 'error' => ''];
        } elseif ($action === 'delete_date') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid date selected.');
            }

            $stmt = $pdo->prepare('DELETE FROM important_dates WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['admin_dates_flash'] = ['msg' => 'Important date removed.', 'error' => ''];
        }
    } catch (Throwable $e) {
        $_SESSION['admin_dates_flash'] = ['msg' => '', 'error' => $e->getMessage()];
    }

    header('Location: admin_important_dates.php');
    exit;
}

$rows = $pdo->query('SELECT id, title, description, event_date, event_type FROM important_dates ORDER BY event_date ASC, id ASC')->fetchAll();
$flash = $_SESSION['admin_dates_flash'] ?? ['msg' => '', 'error' => ''];
unset($_SESSION['admin_dates_flash']);

require 'header.php';
?>

<?php if (!empty($flash['msg'])): ?>
    <div class="flash success-flash" style="margin:18px 20px 0;">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>
<?php if (!empty($flash['error'])): ?>
    <div class="flash error-flash" style="margin:18px 20px 0;">
        <?= e($flash['error']) ?>
    </div>
<?php endif; ?>

<section class="section">
    <div class="section-intro">
        <div>
            <span class="eyebrow">ADMINISTRATION</span>
            <h2>Important Dates</h2>
            <p class="muted">Manage upcoming academic dates visible to students and faculty.</p>
        </div>
    </div>

    <form class="date-admin-form" method="post">
        <input type="hidden" name="action" value="add_date">
        <input required name="title" placeholder="Event title">
        <input name="description" placeholder="Description">
        <input required type="date" name="event_date" min="<?= date('Y-m-d') ?>">
        <select name="event_type">
            <option value="Academic">Academic</option>
            <option value="Enrollment">Enrollment</option>
            <option value="Exam">Exam</option>
            <option value="Holiday">Holiday</option>
            <option value="Other">Other</option>
        </select>
        <button class="btn" type="submit">Add Date</button>
    </form>

    <?php if (!$rows): ?>
        <div class="info-box" style="margin-top:18px;">
            No important dates have been posted yet. Use the form above to add one.
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e(date('M d, Y', strtotime($row['event_date']))) ?></td>
                        <td><b><?= e($row['title']) ?></b></td>
                        <td><?= e($row['event_type']) ?></td>
                        <td><?= e($row['description'] ?: '—') ?></td>
                        <td>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="action" value="delete_date">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="btn danger-btn" type="submit" onclick="return confirm('Remove this date?')">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require 'footer.php'; ?>
