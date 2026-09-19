<?php
require 'admin_bootstrap.php';
$title = 'Faculty Management';

$faculty_flash = $_SESSION['faculty_flash'] ?? ['msg' => '', 'error' => ''];
unset($_SESSION['faculty_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_faculty') {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $facultyId = trim($_POST['faculty_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $facultyId === '' || $department === '' || $password === '') {
            throw new Exception('Please complete all faculty account fields.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid institutional email address.');
        }
        if (strlen($password) < 6) {
            throw new Exception('Temporary password must be at least 6 characters.');
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR student_id = ? LIMIT 1');
        $check->execute([$email, $facultyId]);
        if ($check->fetch()) {
            throw new Exception('That email or faculty ID is already assigned to an account.');
        }

        // The role is deliberately fixed to faculty. Admins cannot create admin accounts here.
        $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,student_id,program) VALUES (?,?,SHA2(?,256),'faculty',?,?)");
        $stmt->execute([$name, $email, $password, $facultyId, $department]);

        $_SESSION['faculty_flash'] = [
            'msg' => 'Faculty account created successfully. Login: ' . $email . ' | Temporary password: ' . $password,
            'error' => ''
        ];
    } catch (Throwable $ex) {
        $_SESSION['faculty_flash'] = ['msg' => '', 'error' => $ex->getMessage()];
    }
    header('Location: admin_faculty.php');
    exit;
}

$faculty = $pdo->query("SELECT * FROM users WHERE role='faculty' ORDER BY name")->fetchAll();
require 'header.php';
?>

<?php if (!empty($faculty_flash['msg'])): ?>
<div class="success" style="margin:18px 20px 0;"><?=e($faculty_flash['msg'])?></div>
<?php endif; ?>
<?php if (!empty($faculty_flash['error'])): ?>
<div class="auth-alert" style="margin:18px 20px 0;"><?=e($faculty_flash['error'])?></div>
<?php endif; ?>

<section class="section">
  <div class="card-head">
    <div>
      <span class="eyebrow">ACCOUNT PROVISIONING</span>
      <h2>Create Faculty Account</h2>
      <p class="muted">Only administrators can provision Professor / Teacher accounts. Faculty cannot create faculty or administrator accounts.</p>
    </div>
  </div>

  <form method="post" class="form-grid" style="margin-top:22px;">
    <input type="hidden" name="action" value="create_faculty">
    <div class="field">
      <label for="faculty-name">Full name</label>
      <input id="faculty-name" name="name" placeholder="Maria Santos" required>
    </div>
    <div class="field">
      <label for="faculty-id">Faculty ID</label>
      <input id="faculty-id" name="faculty_id" placeholder="FAC-2026-001" required>
    </div>
    <div class="field">
      <label for="faculty-email">Institutional email</label>
      <input id="faculty-email" type="email" name="email" placeholder="maria.santos@nu-baliwag.edu.ph" required>
    </div>
    <div class="field">
      <label for="faculty-department">Department / Program</label>
      <input id="faculty-department" name="department" placeholder="College of Engineering and Technology" required>
    </div>
    <div class="field">
      <label for="faculty-password">Temporary password</label>
      <input id="faculty-password" type="text" name="password" placeholder="At least 6 characters" minlength="6" required>
    </div>
    <div class="field" style="display:flex;align-items:end;">
      <button class="btn btn-primary" type="submit">Create Faculty Account</button>
    </div>
  </form>

  <div class="info-box" style="margin-top:22px;">
    <b>Account rule</b>
    <p>Faculty accounts are created by an authorized administrator and are assigned the <strong>faculty</strong> role automatically. The public registration page creates student accounts only.</p>
  </div>
</section>

<section class="section">
  <div class="card-head">
    <div><span class="eyebrow">PEOPLE</span><h2>Faculty Accounts</h2></div>
    <span class="muted"><?=count($faculty)?> faculty accounts</span>
  </div>
  <div class="table-scroll">
    <table class="table">
      <tr><th>Faculty</th><th>Faculty ID</th><th>Email</th><th>Department / Program</th><th>Assigned Classes</th></tr>
      <?php foreach($faculty as $f):
        $fc=$pdo->prepare('SELECT COUNT(*) FROM subject_offerings WHERE faculty_id=?');
        $fc->execute([$f['id']]);
        $n=(int)$fc->fetchColumn();
      ?>
      <tr>
        <td><b><?=e($f['name'])?></b></td>
        <td><?=e($f['student_id'] ?: '—')?></td>
        <td><?=e($f['email'])?></td>
        <td><?=e($f['program'] ?: '—')?></td>
        <td><span class="badge approved"><?=e($n)?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="info-box">
    <b>Faculty assignments</b>
    <p>Assign a faculty member to a subject section from the Schedules window. Assigned teachers automatically appear to students when they choose an enrollment schedule.</p>
    <a href="admin_schedules.php">Open Schedule Management →</a>
  </div>
</section>
<?php require 'footer.php'; ?>
