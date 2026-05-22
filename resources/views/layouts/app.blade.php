<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="ngrok-skip-browser-warning" content="true">
    <title>@yield('title', 'Crypto Payment Gateway')</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        /* Navbar */
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background-color: #fff;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        /* Cards */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .card-header {
            border-bottom: 1px solid #e9ecef;
            background-color: #f8f9fa;
            font-weight: 600;
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }

        /* Forms */
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Alerts */
        .alert {
            border-radius: 6px;
            border: none;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #664d03;
        }

        .alert-info {
            background-color: #cfe2ff;
            color: #084298;
        }

        /* Tables */
        .table {
            border-radius: 6px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Footer */
        footer {
            background-color: #fff;
            border-top: 1px solid #dee2e6;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        /* Badges */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-weight: 500;
        }

        /* Loading spinner */
        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            border-width: 0.2em;
        }

        /* Toast notifications */
        .toast {
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.25rem;
            }

            .card {
                margin-bottom: 1rem;
            }
        }
    </style>

    @yield('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-coins"></i> Crypto Payments
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/payments/checkout/1') }}">
                                <i class="fas fa-shopping-cart"></i> Checkout
                            </a>
                        </li>
                        @if(auth()->user()->is_admin)
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.crypto-gateways.index') }}">
                                    <i class="fas fa-cog"></i> Admin Panel
                                </a>
                            </li>
                        @endif
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> {{ auth()->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        <div class="container">
            <!-- Flash Messages -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h4 class="alert-heading">
                        <i class="fas fa-exclamation-circle"></i> Errors
                    </h4>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-times-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Crypto Payment Gateway</h5>
                    <p class="text-muted">Accept cryptocurrency payments securely and easily.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="text-muted">
                        <small>&copy; {{ date('Y') }} Crypto Payments. All rights reserved.</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

    @yield('scripts')
</body>
</html>
