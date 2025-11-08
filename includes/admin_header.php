<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand text-info fw-bold" href="index.php"><i class="fas fa-film me-2"></i>MovieMania</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo 'active'; ?>" href="index.php">
            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='manage_movies.php') echo 'active'; ?>" href="manage_movies.php">
            <i class="fas fa-film me-1"></i> Manage Movies
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='manage_users.php') echo 'active'; ?>" href="manage_users.php">
            <i class="fas fa-users me-1"></i> Manage Users
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='manage_bookings.php') echo 'active'; ?>" href="manage_bookings.php">
            <i class="fas fa-ticket-alt me-1"></i> Manage Bookings
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='manage_showtimes.php') echo 'active'; ?>" href="manage_showtimes.php">
            <i class="fas fa-clock me-1"></i> Manage Showtimes
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='manage_seats.php') echo 'active'; ?>" href="manage_seats.php">
            <i class="fas fa-chair me-1"></i> Manage Seats
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-danger" href="logout.php">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
