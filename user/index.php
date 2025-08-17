<?php
// Check if user is already logged in
session_start();
$isLoggedIn = false;
$username = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    // Check if session is still valid
    $timeout_duration = isset($_SESSION['remember_me']) && $_SESSION['remember_me'] ?
        (30 * 24 * 60 * 60) : (24 * 60 * 60); // 30 days vs 24 hours

    if ((time() - $_SESSION['last_activity']) <= $timeout_duration) {
        $isLoggedIn = true;
        $username = $_SESSION['username'] ?? $_SESSION['email'] ?? 'User';
        $_SESSION['last_activity'] = time(); // Update last activity
    } else {
        // Session expired, clear it
        session_unset();
        session_destroy();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Buddy - Earn Money Taking Surveys</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ffffff;
            min-width: 200px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 1;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .dropdown-content a {
            color: #374151;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
        }

        .dropdown-content a:hover {
            background-color: #f3f4f6;
            color: #2563eb;
        }

        .dropdown:hover .dropdown-content {
            display: block;
            transform: translateY(0);
        }

        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .feature-card {
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body class="bg-gray-50">
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <a href="/" class="text-2xl font-bold text-blue-600">
                        <span class="text-yellow-500">Task</span>Buddy
                    </a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="#home"
                        class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                    <a href="#features"
                        class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Features</a>
                    <a href="#how-it-works"
                        class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">How It
                        Works</a>
                    <a href="#testimonials"
                        class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Testimonials</a>
                    <a href="#faq"
                        class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">FAQ</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <?php if ($isLoggedIn): ?>
                        <!-- Logged in user menu -->
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                            <a href="../dashboard/dashboard.php"
                                class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition duration-300 font-semibold">
                                Dashboard
                            </a>
                            <a href="auth/logout.php"
                                class="text-gray-700 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium">
                                Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Not logged in menu -->
                        <a href="auth/register.php"
                            class="bg-blue-600 text-white px-8 py-3 rounded-full hover:bg-blue-700 transition duration-300 text-lg font-semibold">
                            Get Started Now
                        </a>
                        <div class="dropdown">
                            <button
                                class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-user"></i>
                                <span>Login</span>
                            </button>
                            <div class="dropdown-content">
                                <a href="auth/login.php"><i class="fas fa-user mr-2"></i>User Login</a>
                                <a href="../Admin/admin_login.php"><i class="fas fa-user-shield mr-2"></i>Admin Login</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="home" class="hero-pattern py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <?php if ($isLoggedIn): ?>
                    <!-- Logged in user hero -->
                    <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-6">
                        Welcome back,
                        <span class="text-blue-600"><?php echo htmlspecialchars($username); ?>!</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                        Ready to earn more money? Check out your dashboard to see available surveys and track your earnings.
                    </p>
                    <div class="flex justify-center space-x-4">
                        <a href="../dashboard/dashboard.php"
                            class="bg-blue-600 text-white px-8 py-3 rounded-full hover:bg-blue-700 transition duration-300 text-lg font-semibold">
                            Go to Dashboard
                        </a>
                        <a href="../surveys/survey.php"
                            class="bg-white text-blue-600 px-8 py-3 rounded-full hover:bg-gray-50 transition duration-300 text-lg font-semibold border border-blue-600">
                            Take Surveys
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Not logged in hero -->
                    <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-6">
                        Turn Your Opinions Into
                        <span class="text-blue-600">Cash</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                        Join thousands of surveyors earning money by sharing their valuable insights.
                        Get paid for your opinions with instant payments and flexible survey schedules.
                    </p>
                    <div class="flex justify-center space-x-4">
                        <a href="auth/register.php"
                            class="bg-blue-600 text-white px-8 py-3 rounded-full hover:bg-blue-700 transition duration-300 text-lg font-semibold">
                            Start Earning Now
                        </a>
                        <a href="#how-it-works"
                            class="bg-white text-blue-600 px-8 py-3 rounded-full hover:bg-gray-50 transition duration-300 text-lg font-semibold border border-blue-600">
                            Learn More
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Why Choose TaskBuddy?</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Instant Payments</h3>
                        <p class="text-gray-600">Get paid immediately after completing surveys. No waiting periods or
                            minimum thresholds.</p>
                    </div>
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Flexible Schedule</h3>
                        <p class="text-gray-600">Take surveys at your convenience. Work from anywhere, anytime.</p>
                    </div>
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Secure & Reliable</h3>
                        <p class="text-gray-600">Your data is protected with industry-standard security measures.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">How It Works</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-blue-600">1</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Sign Up</h3>
                        <p class="text-gray-600">Create your account in minutes</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-blue-600">2</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Complete Profile</h3>
                        <p class="text-gray-600">Tell us about yourself</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-blue-600">3</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Take Surveys</h3>
                        <p class="text-gray-600">Share your opinions</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-blue-600">4</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Get Paid</h3>
                        <p class="text-gray-600">Receive instant payments</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">What Our Surveyors Say</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <div class="flex items-center mb-4">
                            <div class="text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-4">"I've earned over $500 in my spare time. The surveys are
                            interesting and the payment process is smooth."</p>
                        <div class="font-semibold">Sarah M.</div>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <div class="flex items-center mb-4">
                            <div class="text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-4">"Great platform for earning extra income. The surveys are
                            well-designed and payments are always on time."</p>
                        <div class="font-semibold">John D.</div>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <div class="flex items-center mb-4">
                            <div class="text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-4">"I love how flexible it is. I can take surveys whenever I want,
                            and the earnings add up quickly."</p>
                        <div class="font-semibold">Emily R.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Frequently Asked Questions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-2">How much can I earn?</h3>
                        <p class="text-gray-600">Earnings vary based on survey length and complexity. Most surveys pay
                            between $1-$5, and active surveyors typically earn $50-$200 per month.</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-2">How do I get paid?</h3>
                        <p class="text-gray-600">Payments are processed instantly through our secure payment system. You
                            can withdraw your earnings at any time.</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-2">How often will I receive surveys?</h3>
                        <p class="text-gray-600">New surveys are available daily. The number of surveys you receive
                            depends on your profile and market research needs.</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold mb-2">Is my information secure?</h3>
                        <p class="text-gray-600">Yes, we use industry-standard encryption and security measures to
                            protect your personal information.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-blue-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <?php if ($isLoggedIn): ?>
                    <!-- Logged in user CTA -->
                    <h2 class="text-3xl font-bold text-white mb-8">Ready to Continue Earning?</h2>
                    <p class="text-xl text-blue-100 mb-8">Your next survey is waiting! Check your dashboard for new
                        opportunities.</p>
                    <div class="flex justify-center space-x-4">
                        <a href="../dashboard/dashboard.php"
                            class="bg-white text-blue-600 px-8 py-3 rounded-full hover:bg-gray-100 transition duration-300 text-lg font-semibold inline-block">
                            View Dashboard
                        </a>
                        <a href="../surveys/survey.php"
                            class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-full hover:bg-white hover:text-blue-600 transition duration-300 text-lg font-semibold inline-block">
                            Take Survey
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Not logged in CTA -->
                    <h2 class="text-3xl font-bold text-white mb-8">Ready to Start Earning?</h2>
                    <p class="text-xl text-blue-100 mb-8">Join thousands of surveyors who are already earning with
                        TaskBuddy.</p>
                    <a href="auth/register.php"
                        class="bg-white text-blue-600 px-8 py-3 rounded-full hover:bg-gray-100 transition duration-300 text-lg font-semibold inline-block">
                        Join Now
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Admin Section -->
        <section id="admin" class="py-20 bg-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Admin Portal</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Admin Dashboard</h3>
                        <p class="text-gray-600 mb-4">Access the admin control panel to manage the platform.</p>
                        <a href="../Admin/admindashboard.php"
                            class="inline-flex items-center text-blue-600 hover:text-blue-700">
                            Access Dashboard <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">User Management</h3>
                        <p class="text-gray-600 mb-4">Manage user accounts and permissions.</p>
                        <a href="../Admin/manage_users.php"
                            class="inline-flex items-center text-blue-600 hover:text-blue-700">
                            Manage Users <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-poll"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Survey Management</h3>
                        <p class="text-gray-600 mb-4">Create and manage surveys on the platform.</p>
                        <a href="../Admin/manage_surveys.php"
                            class="inline-flex items-center text-blue-600 hover:text-blue-700">
                            Manage Surveys <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Withdrawal Management</h3>
                        <p class="text-gray-600 mb-4">Process and manage user withdrawal requests.</p>
                        <a href="../Admin/admin_withdrawals.php"
                            class="inline-flex items-center text-blue-600 hover:text-blue-700">
                            Manage Withdrawals <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-blue-600 text-4xl mb-4">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Admin Management</h3>
                        <p class="text-gray-600 mb-4">Add and manage admin accounts.</p>
                        <a href="../Admin/create_new_admin.php"
                            class="inline-flex items-center text-blue-600 hover:text-blue-700">
                            Manage Admins <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">
                        <span class="text-yellow-500">Task</span>Buddy
                    </h3>
                    <p class="text-gray-400">Turn your opinions into cash with the world's leading survey platform.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#home" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white">Features</a></li>
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white">How It Works</a></li>
                        <li><a href="#faq" class="text-gray-400 hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Connect With Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>© 2025 TaskBuddy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('[data-mobile-menu]');
        const mobileMenu = document.querySelector('[data-mobile-menu-items]');

        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>
</body>

</html>