<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">

    <nav class="menu">
        <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
            <span>Dashboard</span>
        </a>
        
        <a href="services-category.php" class="menu-item <?php echo ($current_page == 'services-category.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            <span>Services Category</span>
        </a>
        
        <a href="schedule-customer.php" class="menu-item <?php echo ($current_page == 'schedule-customer.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <path d="M16 2v4"/>
                <path d="M8 2v4"/>
                <path d="M3 10h18"/>
            </svg>
            <span>Schedule Customer</span>
        </a>
        
        <a href="schedule-therapist.php" class="menu-item <?php echo ($current_page == 'schedule-therapist.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l2 7h7l-5.5 4 2 7L12 16l-5.5 4 2-7L3 9h7z"/>
            </svg>
            <span>Schedule Therapist</span>
        </a>
        
        <a href="customer-data.php" class="menu-item <?php echo ($current_page == 'customer-data.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Customer Data</span>
        </a>
        
        <a href="therapist-data.php" class="menu-item <?php echo ($current_page == 'therapist-data.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>Therapist Data</span>
        </a>
        
        <a href="room-booking.php" class="menu-item <?php echo ($current_page == 'room-booking.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <path d="M16 2v4"/>
                <path d="M8 2v4"/>
                <path d="M3 10h18"/>
                <path d="M8 14h.01"/>
                <path d="M12 14h.01"/>
                <path d="M16 14h.01"/>
                <path d="M8 18h.01"/>
                <path d="M12 18h.01"/>
                <path d="M16 18h.01"/>
            </svg>
            <span>Room Booking</span>
        </a>

        <a href="voucher.php" class="menu-item <?php echo ($current_page == 'voucher.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="5" width="18" height="14" rx="2" ry="2"/>
                <path d="M16 5v2a2 2 0 0 0 2 2h2"/>
                <path d="M8 19v-2a2 2 0 0 0-2-2H4"/>
                <circle cx="12" cy="12" r="2"/>
            </svg>
            <span>Voucher</span>
        </a>

        <a href="blog-management.php" class="menu-item <?php echo ($current_page == 'blog-management.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Blog Management</span>
        </a>

        <a href="content-management.php" class="menu-item <?php echo ($current_page == 'content-management.php') ? 'active' : ''; ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
            </svg>
            <span>Content Management</span>
        </a>
    </nav>
    
    <a href="logout.php" class="logout">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        <span>Log Out</span>
    </a>
</aside>