<html><head><base href="." />
<style>
  body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  }

  nav {
    background: rgba(255, 255, 255, 0.9);
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: fixed;
    width: 100%;
    top: 0;
    display: flex;
    justify-content: flex-end;
    z-index: 1000;
  }

  .student-btn {
  padding: 0.5rem 1.5rem; /* Reduced padding */
  font-size: 0.9rem; /* Smaller font size */
  border: none;
  border-radius: 8px;
  background: #2ecc71;
  color: white;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
}


  .student-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }

  .page-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    gap: 2rem;
  }

  .container {
    text-align: center;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    margin-top: 60px;
  }

  h1 {
    color: #2c3e50;
    margin-bottom: 2rem;
    font-size: 2.5rem;
  }

  .button-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
  }

  .role-button {
    padding: 1rem 3rem;
    font-size: 1.2rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
    width: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
  }

  .admin { background: #e74c3c; }
  .instructor { background: #3498db; }

  .role-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }

  .role-button:active {
    transform: translateY(1px);
  }

  .icon {
  width: 16px; /* Smaller width */
  height: 16px; /* Smaller height */
  fill: currentColor;
}

</style>
</head>
<body>
  <!-- <nav>
    <button onclick="window.location.href='student_login.php'" class="student-btn">
      <svg class="icon" viewBox="0 0 24 24">
        <path d="M12 3L1 9l11 6l9-4.91V17h2V9L12 3z M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
      </svg>
      Student Login
    </button>
  </nav> -->

  <div class="page-wrapper">
    <div class="container">
      <h1>Welcome to Learning Portal</h1>
      <div class="button-container">
        <button onclick="window.location.href='admin_login.php'" class="role-button admin">
          <svg class="icon" viewBox="0 0 24 24">
            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12c5.16-1.26 9-6.45 9-12V5L12 1zm0 2.18l7 3.5v4.82c0 4.34-2.99 8.38-7 9.5c-4.01-1.12-7-5.16-7-9.5V6.68l7-3.5z M12 11.5c1.38 0 2.5-1.12 2.5-2.5S13.38 6.5 12 6.5S9.5 7.62 9.5 9s1.12 2.5 2.5 2.5z M12 12.5c-1.67 0-5 .83-5 2.5V16h10v-1c0-1.67-3.33-2.5-5-2.5z"/>
          </svg>
          Admin
        </button>
        <button onclick="window.location.href='instructor_login.php'" class="role-button instructor">
          <svg class="icon" viewBox="0 0 24 24">
            <path d="M20 17h2v-2h-2v2zm2-4V9h-2v4h2zm-2 8h2v-2h-2v2zM16 5v14c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2zm-4 8c0-.55-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h4c.55 0 1-.45 1-1zm0-4c0-.55-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h4c.55 0 1-.45 1-1zm0 8c0-.55-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h4c.55 0 1-.45 1-1z"/>
          </svg>
          Instructor Login
        </button>
      </div>
    </div>
  </div>

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