<?php
// Shared Admin Sidebar
// Expects $active_menu to be one of: dashboard, users, property_types, properties, locations, bookings, payments, reports, settings
?>
<div class="col-md-3 col-lg-2 px-0 bg-dark">
    <div class="p-3">
        <h4 class="text-white mb-4">
            <i class="fas fa-cog me-2"></i>Admin Panel
        </h4>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='dashboard') ? 'active text-white' : 'text-white-50'; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='users') ? 'active text-white' : 'text-white-50'; ?>" href="users.php">
                    <i class="fas fa-users me-2"></i>Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='properties') ? 'active text-white' : 'text-white-50'; ?>" href="properties.php">
                    <i class="fas fa-home me-2"></i>Properties
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='property_types') ? 'active text-white' : 'text-white-50'; ?>" href="property-types.php">
                    <i class="fas fa-tags me-2"></i>Property Types
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='locations') ? 'active text-white' : 'text-white-50'; ?>" href="locations.php">
                    <i class="fas fa-map-marker-alt me-2"></i>Locations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='bookings') ? 'active text-white' : 'text-white-50'; ?>" href="bookings.php">
                    <i class="fas fa-calendar me-2"></i>Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='payments') ? 'active text-white' : 'text-white-50'; ?>" href="payments.php">
                    <i class="fas fa-credit-card me-2"></i>Payments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='reports') ? 'active text-white' : 'text-white-50'; ?>" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i>Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_menu==='settings') ? 'active text-white' : 'text-white-50'; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-white-50" href="../../frontend/index.php">
                    <i class="fas fa-external-link-alt me-2"></i>View Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50" href="../../frontend/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
    </div>


