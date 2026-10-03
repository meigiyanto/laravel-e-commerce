# Laravel E-Commerce Store

A full-stack e-commerce web application built with Laravel 13, PHP, MySQL/MariaDB, Bootstrap 5, and Vite. This project is designed as a portfolio application demonstrating authentication, storefront functionality, shopping cart management, checkout, order processing, inventory administration, product reviews, wishlist and comparison features, and online payment integration.

## ✨ Features

### 🛍️ Storefront

* Responsive e-commerce storefront
* Homepage with product categories and latest products
* Product catalog with pagination
* Product search by name and description
* Product filtering by category
* Product detail pages
* Related products based on category
* Product stock availability
* Shopping cart
* AJAX add-to-cart interactions
* AJAX cart quantity updates
* Dynamic cart count and subtotal updates
* Wishlist
* Product comparison
* Product reviews and ratings
* Customer order history
* Order detail pages

### 🛒 Shopping Cart & Checkout

* Authenticated shopping cart
* Add products with quantity validation
* Server-side stock validation
* Update cart quantities
* Remove cart items
* Checkout form with customer and shipping information
* Server-side price and total calculation
* Automatic stock deduction when an order is created
* Automatic order number generation
* Empty-cart validation
* Transaction-safe checkout using database transactions

### 💳 Payment Integration

The application supports multiple payment methods:

* **Stripe** — card payments using Stripe PaymentIntent
* **Midtrans** — payment processing using Midtrans Snap
* **Cash on Delivery (COD)**
* Payment status synchronization
* Stripe webhook handling
* Midtrans notification handling
* Payment verification before an order is considered successfully paid
* Payment records associated with orders

> Stripe and Midtrans require their respective API credentials to be configured in the environment.

### 👤 Authentication & Customer Account

Authentication is implemented using Laravel Breeze and includes:

* User registration
* Login and logout
* Forgot password
* Password reset
* Email verification
* Password confirmation
* Password update
* Profile update
* Account deletion
* Customer dashboard
* Personal order history

### 🔐 Admin Dashboard

The application provides an admin area protected by the `admin` middleware.

#### Product Management

* Create products
* Edit products
* Delete products
* Assign categories and sub-categories
* Manage product prices
* Manage product stock

#### Category Management

* Create categories
* Edit categories
* Delete categories
* Category descriptions and slugs

#### Sub-category Management

* Create sub-categories
* Edit sub-categories
* Delete sub-categories
* Associate sub-categories with categories
* Search sub-categories

#### Inventory Management

* Search products
* Filter products by stock status
* View out-of-stock products
* View low-stock products
* View products currently in stock
* Direct stock updates
* Add stock
* Subtract stock
* Low-stock monitoring

#### Order Management

* View all orders
* Search orders
* Filter orders by status
* View order details
* Update order status
* Supported order statuses:

  * Pending
  * Processing
  * Shipped
  * Completed
  * Cancelled
* Automatically restore product stock when an order is cancelled

#### User Management

* View registered users
* Search users by name or email

### ❤️ Wishlist

Authenticated customers can:

* Add products to wishlist
* View wishlist
* Remove products from wishlist

### ⚖️ Product Comparison

Customers can compare products using:

* Add product to comparison
* Remove individual products
* Clear comparison list
* Dedicated comparison page

### ⭐ Product Reviews

Customers can:

* Submit product reviews
* Rate products
* Update their reviews
* Delete their reviews

Reviews are restricted to eligible completed orders.

### 🎨 Frontend

* Blade templating
* Bootstrap 5
* Alpine.js
* Vite
* Bootstrap Icons
* Font Awesome
* Themify Icons
* SweetAlert2
* Toastr
* DataTables
* Chart.js
* Responsive layouts
* Custom storefront styling
* Custom dashboard/admin styling

## 🧰 Tech Stack

| Layer           | Technology            |
| --------------- | --------------------- |
| Backend         | Laravel 13            |
| Language        | PHP 8.3+              |
| Database        | MySQL / MariaDB       |
| Authentication  | Laravel Breeze        |
| Template Engine | Blade                 |
| CSS Framework   | Bootstrap 5           |
| JavaScript      | JavaScript, Alpine.js |
| Build Tool      | Vite                  |
| Payments        | Stripe, Midtrans      |
| Notifications   | SweetAlert2, Toastr   |
| Data Tables     | DataTables            |
| Charts          | Chart.js              |

## 📁 Project Structure

```text
laravel-e-commerce/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   └── Auth/
│   │   └── Middleware/
│   ├── Models/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── admin/
│       ├── auth/
│       ├── layouts/
│       ├── orders/
│       └── storefront/
├── routes/
├── storage/
├── tests/
├── composer.json
├── package.json
└── vite.config.js
```

## 🚀 Installation

### Requirements

Make sure the following are installed:

* PHP 8.3 or newer
* Composer
* Node.js and npm
* MySQL or MariaDB
* Git

### 1. Clone the repository

```bash
git clone https://github.com/meigiyanto/laravel-e-commerce.git
cd laravel-e-commerce
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure environment

Copy the example environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel-e-commerce
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run database migrations

```bash
php artisan migrate
```

The repository also contains a database dump:

```text
laravel_e_commerce_termux.sql
```

This can be used when you want to restore the prepared database instead of starting with an empty database.

### 5. Install frontend dependencies

```bash
npm install --ignore-scripts
```

Build the frontend assets:

```bash
npm run build
```

For development:

```bash
npm run dev
```

### 6. Start Laravel

```bash
php artisan serve
```

Open:

```text
http://localhost:8000
```

## 💳 Payment Configuration

### Stripe

Add the following variables to `.env`:

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

### Midtrans

Add:

```env
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

For local development, keep:

```env
MIDTRANS_IS_PRODUCTION=false
```

Never commit real payment credentials to Git.

## 👨‍💼 Admin Access

The admin section is available under:

```text
/admin
```

Admin routes are protected by authentication and the application's `admin` middleware.

A user must have the appropriate admin role in the database to access the admin dashboard.

## 🧪 Testing

Run the Laravel test suite:

```bash
php artisan test
```

Or:

```bash
composer test
```

## 🔒 Security

The application implements several server-side safeguards:

* Authentication-protected customer routes
* Admin authorization middleware
* Order ownership validation
* Cart ownership validation
* Server-side product validation
* Server-side stock validation
* Database transactions during checkout
* Product row locking during stock-sensitive operations
* Payment verification through payment-provider APIs
* Stripe webhook handling
* Midtrans notification handling
* CSRF protection
* Validation of payment/order relationships

Payment amounts and product prices are calculated from trusted server-side data rather than browser-submitted prices.

## 🔄 E-Commerce Workflow

```text
                     ┌──────────────┐
                     │   Customer   │
                     └──────┬───────┘
                            │
                            ▼
                  ┌───────────────────┐
                  │ Browse Products   │
                  │ Search / Filter   │
                  └─────────┬─────────┘
                            │
                            ▼
                  ┌───────────────────┐
                  │ Product Details   │
                  │ Wishlist / Compare│
                  └─────────┬─────────┘
                            │
                            ▼
                  ┌───────────────────┐
                  │    Add to Cart    │
                  └─────────┬─────────┘
                            │
                            ▼
                  ┌───────────────────┐
                  │     Checkout      │
                  └─────────┬─────────┘
                            │
                  ┌─────────┼─────────┐
                  ▼         ▼         ▼
               Stripe    Midtrans    COD
                  │         │         │
                  └─────────┼─────────┘
                            ▼
                  ┌───────────────────┐
                  │       Order       │
                  └─────────┬─────────┘
                            │
                            ▼
                  ┌───────────────────┐
                  │  Order Tracking   │
                  └─────────┬─────────┘
                            │
                            ▼
                  ┌───────────────────┐
                  │ Product Review    │
                  └───────────────────┘
```

### Admin Workflow

```text
                    ┌──────────────┐
                    │    Admin     │
                    └──────┬───────┘
                           │
             ┌─────────────┼─────────────┐
             ▼             ▼             ▼
        Categories     Products      Inventory
             │             │             │
             └─────────────┼─────────────┘
                           ▼
                         Orders
                           │
                    ┌──────┴──────┐
                    ▼             ▼
                Processing     Cancelled
                    │             │
                    ▼             ▼
                Completed    Restore Stock
```

## 🎯 Portfolio Purpose

This project was created as a web development portfolio project to demonstrate practical full-stack development skills.

Key areas demonstrated by the project include:

* Laravel application architecture
* MVC development
* Eloquent ORM
* Relational database design
* Authentication and authorization
* CRUD operations
* E-commerce business logic
* Shopping cart implementation
* AJAX interactions
* Checkout processing
* Inventory management
* Payment gateway integration
* Webhook processing
* Transaction handling
* Concurrency-safe stock management
* Server-side validation
* Responsive frontend development

## 📌 Project Status

The application currently contains the core functionality required for a functional e-commerce workflow, from product discovery and cart management through checkout, payment processing, order management, inventory management, and customer reviews.

The project can be further extended with features such as shipping integration, coupons and promotions, product variants, advanced analytics, automated email notifications, and additional testing.

## 📄 License

This project is open-sourced under the MIT License.
