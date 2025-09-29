<?php
/**
 * Example Page Using Navbar Component
 * This shows how to use the navbar in any page
 */

// Include config
require_once __DIR__ . '/../config/config.php';

// Set page title
$page_title = 'Example Page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="row">
            <div class="col-12">
                <h1 class="display-4 text-center mb-5">Welcome to <?php echo APP_NAME; ?></h1>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-search fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Search Properties</h5>
                                <p class="card-text">Find your perfect rental home with our advanced search features.</p>
                                <a href="search.php" class="btn btn-primary">Start Searching</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-3x text-success mb-3"></i>
                                <h5 class="card-title">List Your Property</h5>
                                <p class="card-text">Are you a property owner? List your property and find tenants.</p>
                                <a href="add-property.php" class="btn btn-success">List Property</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-heart fa-3x text-danger mb-3"></i>
                                <h5 class="card-title">Save Favorites</h5>
                                <p class="card-text">Save properties you like and access them anytime.</p>
                                <a href="wishlist.php" class="btn btn-danger">View Wishlist</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
