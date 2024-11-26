<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./assets/theme.css">
  <link rel="stylesheet" href="./assets/index.css">
  <title>Document</title>
</head>

<body>
  <section id="login">
    <div class="wrapper">
      <div class="login-container">
        <h1>Welcome to Learning Portal</h1>
        <div class="button-container">
          <button onclick="window.location.href='admin_login.php'" class="role-button btn-primary">
            <i class="bi bi-person-workspace"></i>
            <span>Admin</span>
          </button>
          <button onclick="window.location.href='instructor_login.php'" class="role-button btn-secondary">
            <i class="bi bi-person-lines-fill"></i>
            <span>Instructor Login</span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <script>
    const buttons = document.querySelectorAll('.role-button, .student-btn');
    buttons.forEach(button => {
      button.addEventListener('mouseenter', () => {
        const hoverSound = new Audio('data:audio/wav;base64,UklGRnQGAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YU8GAAAAAP//AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
        hoverSound.volume = 0.2;
        hoverSound.play().catch(e => console.log('Audio play failed:', e));
      });
    });
  </script>
</body>

</html>