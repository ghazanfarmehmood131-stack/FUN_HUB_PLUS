<?php
/**
 * ARCHIVE Ledger - Account & Session Access
 * Sign in, account registration, session status, and profile view.
 * Persists user records in MySQL database.
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Account Access - ARCHIVE';
$activeNav = 'auth';
$pageTag   = 'ACCESS';

$alertMessage = '';
$alertType = 'error';
$nextPath = $_GET['next'] ?? $_POST['next'] ?? '';
if (!is_string($nextPath) || !preg_match('/^video\.php\?id=[1-9][0-9]*$/D', $nextPath)) {
  $nextPath = '';
}
$authRedirect = $nextPath !== '' ? $nextPath : 'index.php';
$showSignup = ($_GET['mode'] ?? '') === 'signup' || ($_POST['show_signup'] ?? '') === '1';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'logout') {
        $_SESSION = [];
        if (session_id()) session_destroy();
        header('Location: auth.php');
        exit;
    }

    if ($action === 'login') {
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = 'Please enter a valid email address.';
        } elseif (empty($password)) {
            $alertMessage = 'Please enter your password.';
        } else {
            $db = getDb();
            if ($db) {
                $stmt = $db->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                  session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    header('Location: ' . $authRedirect);
                    exit;
                } else {
                    $alertMessage = 'Invalid email or password combination.';
                }
            } else {
                $alertMessage = 'Database connection currently unavailable.';
            }
        }
    }

    if ($action === 'signup') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (mb_strlen($name) < 2) {
            $alertMessage = 'Please provide your name (minimum 2 characters).';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $alertMessage = 'Password must be at least 6 characters.';
        } else {
            $db = getDb();
            if ($db) {
                $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check->execute([$email]);
                if ($check->fetch()) {
                    $alertMessage = 'An account with this email is already registered.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $db->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                    $ins->execute([$name, $email, $hash]);
                    $newId = $db->lastInsertId();

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'user';
                    header('Location: ' . $authRedirect);
                    exit;
                }
            } else {
                $alertMessage = 'Database connection currently unavailable.';
            }
        }
    }
}

$currentUser = getCurrentUser();
$watchlistCount = 0;
if ($currentUser) {
    $watchlistCount = getUserWatchlistCount($currentUser['id']);
}

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Content -->
  <main class="site-main">
    <div class="container section">

      <!-- Form Box Container -->
      <div class="auth-wrapper" id="authBox">
        
        <?php if (!empty($alertMessage)): ?>
          <div style="padding: 12px; margin-bottom: 20px; font-size: 13px; border: 1px solid var(--accent); background-color: var(--bg-surface); color: var(--accent);">
            <?= e($alertMessage) ?>
          </div>
        <?php endif; ?>

        <?php if ($currentUser): ?>
          <!-- Active Profile View -->
          <div id="activeProfileCard">
            <div style="text-align: center; margin-bottom: 24px;">
              <div style="font-family: var(--font-display); font-size: 13px; font-weight: 700; color: var(--accent); letter-spacing: 0.1em; margin-bottom: 6px;">
                ACTIVE LEDGER SESSION
              </div>
              <h3 id="profileName" style="font-size: 28px; margin-bottom: 4px;"><?= e($currentUser['name']) ?></h3>
              <div id="profileEmail" style="color: var(--text-dim); font-size: 14px;"><?= e($currentUser['email']) ?></div>
            </div>

            <table class="data-table" style="margin-bottom: 24px;">
              <tbody>
                <tr>
                  <th>ACCOUNT ROLE</th>
                  <td><?= strtoupper(e($currentUser['role'])) ?></td>
                </tr>
                <tr>
                  <th>DATABASE USER ID</th>
                  <td>#<?= (int)$currentUser['id'] ?></td>
                </tr>
                <tr>
                  <th>SAVED TITLES</th>
                  <td id="profileWatchlistCount"><?= (int)$watchlistCount ?> records in MySQL</td>
                </tr>
                <tr>
                  <th>PERSISTENCE LAYER</th>
                  <td>MySQL Relational Engine (InnoDB)</td>
                </tr>
              </tbody>
            </table>

            <div style="display: flex; gap: 10px;">
              <?php if ($nextPath !== ''): ?><a href="<?= e($nextPath) ?>" class="btn btn-primary" style="flex: 1;">CONTINUE TO VIDEO</a><?php endif; ?>
              <a href="watchlist.php" class="btn btn-primary" style="flex: 1;">VIEW WATCHLIST</a>
              <form method="POST" action="auth.php" style="display: inline;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-secondary">SIGN OUT</button>
              </form>
            </div>
          </div>

        <?php else: ?>
          <!-- Auth Tabs -->
          <div class="auth-tabs" id="authTabsHeader">
            <button type="button" class="auth-tab<?= $showSignup ? '' : ' active' ?>" id="tabLoginBtn">SIGN IN</button>
            <button type="button" class="auth-tab<?= $showSignup ? ' active' : '' ?>" id="tabSignupBtn">CREATE ACCOUNT</button>
          </div>

          <!-- Feedback Alert for JS -->
          <div id="authAlert" style="display: none; padding: 12px; margin-bottom: 20px; font-size: 13px; border: 1px solid var(--border-bold); background-color: var(--bg-canvas);"></div>

          <!-- Login Form -->
          <form id="loginForm" method="POST" action="auth.php" autocomplete="on"<?= $showSignup ? ' style="display: none;"' : '' ?>>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="next" value="<?= e($nextPath) ?>">
            <input type="hidden" name="show_signup" value="<?= $showSignup ? '1' : '0' ?>">
            <div class="form-group">
              <label for="loginEmail" class="form-label">EMAIL ADDRESS</label>
              <input type="email" id="loginEmail" name="email" class="form-input" placeholder="archivist@domain.com" required />
            </div>
            <div class="form-group">
              <label for="loginPassword" class="form-label">PASSWORD</label>
              <input type="password" id="loginPassword" name="password" class="form-input" placeholder="••••••••" required />
            </div>
            <div class="form-group">
              <label class="form-checkbox-label">
                <input type="checkbox" id="loginRemember" checked />
                <span>Remember session on this device</span>
              </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
              SIGN IN TO ACCOUNT
            </button>
            <div class="auth-helper-text">
              Credentials verified against MySQL database with password_hash security.
            </div>
          </form>

          <!-- Signup Form (Initially hidden) -->
          <form id="signupForm" method="POST" action="auth.php"<?= $showSignup ? '' : ' style="display: none;"' ?> autocomplete="on">
            <input type="hidden" name="action" value="signup">
            <input type="hidden" name="next" value="<?= e($nextPath) ?>">
            <input type="hidden" name="show_signup" value="1">
            <div class="form-group">
              <label for="signupName" class="form-label">YOUR FULL NAME</label>
              <input type="text" id="signupName" name="name" class="form-input" placeholder="e.g. Alex Vance" required />
            </div>
            <div class="form-group">
              <label for="signupEmail" class="form-label">EMAIL ADDRESS</label>
              <input type="email" id="signupEmail" name="email" class="form-input" placeholder="archivist@domain.com" required />
            </div>
            <div class="form-group">
              <label for="signupPassword" class="form-label">CREATE PASSWORD</label>
              <input type="password" id="signupPassword" name="password" class="form-input" placeholder="Minimum 6 characters" required />
            </div>
            <div class="form-group">
              <label class="form-checkbox-label">
                <input type="checkbox" id="signupTerms" required />
                <span>I agree to the <a href="terms.php" style="text-decoration: underline; color: var(--text-bright);">Terms of Service</a> and <a href="privacy.php" style="text-decoration: underline; color: var(--text-bright);">Privacy Policy</a>.</span>
              </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
              REGISTER PROFILE
            </button>
          </form>
        <?php endif; ?>

      </div>

    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const tabLogin = document.getElementById('tabLoginBtn');
      const tabSignup = document.getElementById('tabSignupBtn');
      const loginForm = document.getElementById('loginForm');
      const signupForm = document.getElementById('signupForm');

      if (tabLogin && tabSignup && loginForm && signupForm) {
        tabLogin.addEventListener('click', function() {
          tabLogin.classList.add('active');
          tabSignup.classList.remove('active');
          loginForm.style.display = 'block';
          signupForm.style.display = 'none';
        });

        tabSignup.addEventListener('click', function() {
          tabSignup.classList.add('active');
          tabLogin.classList.remove('active');
          loginForm.style.display = 'none';
          signupForm.style.display = 'block';
        });
        <?php if ($showSignup): ?>
        tabSignup.click();
        <?php endif; ?>
      }
    });
  </script>

<?php include __DIR__ . '/includes/footer.php'; ?>
