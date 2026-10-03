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

## 📁 Pr
