# 💊 MedIn AI — Enterprise Pharmacy Management System

![PHP](https://img.shields.io/badge/Backend-PHP%208.2-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-TiDB%20Serverless-4479A1?logo=mysql&logoColor=white)
![Render](https://img.shields.io/badge/Deployed-Render-46E3B7?logo=render&logoColor=white)
![Vercel](https://img.shields.io/badge/Frontend-Vercel-000000?logo=vercel&logoColor=white)

A fully cloud-deployed, role-based pharmacy management platform supporting **B2B supply chain operations**, **B2C e-commerce**, **Point-of-Sale billing**, **FIFO inventory management**, and an **AI-powered assistant** — all built from scratch with PHP, JavaScript, and MySQL.

---

## 🌐 Live Deployment

| Component | URL |
|-----------|-----|
| **Frontend (Vercel)** | [https://roobi-medin.vercel.app](https://roobi-medin.vercel.app) |
| **Backend (Render)** | [https://roobi-backend.onrender.com](https://roobi-backend.onrender.com) |
| **GitHub Repository** | [https://github.com/Saadulhassan0/roobi-medin](https://github.com/Saadulhassan0/roobi-medin) |

### 🔑 Default Admin Login
| Field | Value |
|-------|-------|
| Email | `admin@medin.com` |
| Password | `Admin@123` |
| Role | `Admin` |

---

## ✨ Key Features

### 👑 Admin Dashboard
- **User Management** — Full CRUD for all system users with role assignment
- **Supplier Management** — Add, edit, delete supplier companies
- **Inventory Management** — Medicine catalog with supplier linking & expiry tracking
- **Purchase Orders (B2B)** — Create orders to suppliers, approve/reject deliveries
- **Revenue Analytics** — 7-day revenue chart + inventory category distribution
- **Real-time Chat** — Built-in messaging system on each purchase order

### 💊 Pharmacist Panel
- **Point-of-Sale (POS)** — Fast billing system with search, discounts, bag charges, and tax
- **FIFO Stock Deduction** — Sells medicines from the batch expiring soonest first
- **Batch Inventory** — View all batches with status management (Active, Quarantined, Disposed, Returned)
- **Smart Alerts** — Low stock, out of stock, expiring soon, and already expired warnings

### 🚚 Supplier Portal
- **Order Inbox** — View incoming purchase orders from admin
- **Order Lifecycle** — Accept → Ship → Await Admin Approval
- **Delivery History** — Track completed and rejected orders
- **B2B Chat** — Communicate directly with admin on each order

### 🛒 Customer E-Commerce
- **Medicine Shop** — Browse, search, and filter medicines by category
- **Smart Recommendations** — AI-powered suggestions based on purchase history
- **Shopping Cart** — Add to cart with real-time stock validation
- **Checkout** — Supports Cash on Delivery (COD) and Card payments
- **Order Tracking** — View order history with full item snapshots
- **Profile Management** — Save multiple delivery addresses, manage wishlist

### 🤖 AI Assistant
- Natural language chatbot for medicine information, pricing, and navigation help
- Queries live database for real-time medicine prices and stock levels

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla) |
| **Backend** | PHP 8.2, Apache (Docker) |
| **Database** | TiDB Serverless (MySQL-compatible cloud DB) |
| **Frontend Hosting** | Vercel |
| **Backend Hosting** | Render (Docker container) |
| **Security** | Forced SSL/TLS, Bcrypt password hashing, Prepared SQL statements, Session-based auth |
| **Version Control** | Git + GitHub |

---

## 📁 Project Structure

```
roobi-medin/
├── public/                    # Frontend static files
│   ├── index.html             # Landing / Login page
│   └── assets/
│       ├── css/               # Stylesheets (dashboard.css, style.css, theme.css)
│       └── js/                # JavaScript (dashboard.js)
│
├── app/
│   ├── core/                  # Core classes
│   │   ├── Database.php       # PDO database connection (SSL-enabled)
│   │   └── Session.php        # Session management
│   │
│   ├── api/                   # REST API endpoints
│   │   ├── login.php          # POST - User login
│   │   ├── register.php       # POST - User registration
│   │   ├── verify_otp.php     # POST - Email OTP verification
│   │   ├── logout.php         # GET  - Session logout
│   │   ├── ai_chat.php        # POST - AI chatbot
│   │   ├── notifications_api.php  # GET - Role-based notifications
│   │   │
│   │   ├── admin/             # Admin-only APIs
│   │   │   ├── po_api.php         # Purchase Orders CRUD
│   │   │   ├── inventory_crud.php # Medicine CRUD
│   │   │   ├── supplier_crud.php  # Supplier CRUD
│   │   │   ├── user_crud.php      # User CRUD
│   │   │   └── analytics.php      # Revenue & inventory analytics
│   │   │
│   │   ├── pharmacist/        # Pharmacist-only APIs
│   │   │   ├── dashboard_api.php  # Dashboard KPIs
│   │   │   ├── billing_api.php    # POS billing (FIFO)
│   │   │   ├── inventory_api.php  # Batch management
│   │   │   └── alerts_api.php     # Stock & expiry alerts
│   │   │
│   │   ├── customer/          # Customer-only APIs
│   │   │   ├── shop_api.php       # Browse & search medicines
│   │   │   ├── cart_api.php       # Shopping cart
│   │   │   ├── checkout_api.php   # Order checkout
│   │   │   ├── orders_api.php     # Order history
│   │   │   └── profile_api.php    # Addresses & wishlist
│   │   │
│   │   ├── supplier/          # Supplier-only APIs
│   │   │   └── orders_api.php     # View/manage purchase orders
│   │   │
│   │   └── shared/            # Shared APIs
│   │       └── chat_api.php       # B2B order chat
│   │
│   └── views/                 # PHP view templates
│       ├── admin/             # Admin dashboard pages
│       ├── pharmacist/        # Pharmacist dashboard pages
│       ├── supplier/          # Supplier dashboard pages
│       ├── customer/          # Customer dashboard pages
│       └── layouts/           # Shared layout components
│
├── database/
│   └── schema.sql             # Database schema
│
├── setup_db.php               # Database table creation script
├── update_db.php              # Database migration script
├── migrate_ecommerce.php      # E-commerce tables migration
├── migrate_batches.php        # Batch inventory migration
│
├── Dockerfile                 # Docker configuration for Render
├── render.yaml                # Render Blueprint configuration
├── vercel.json                # Vercel routing configuration
├── .env.example               # Environment variables template
├── API_DOCS.md                # Complete API documentation
└── README.md                  # This file
```

---

## 🗄️ Database Schema (15 Tables)

| # | Table | Description |
|---|-------|-------------|
| 1 | `users` | All system users (admin, pharmacist, supplier, customer) |
| 2 | `suppliers` | Registered supplier companies |
| 3 | `medicines` | Master medicine catalog |
| 4 | `medicine_batches` | Batch-level inventory with expiry & status tracking |
| 5 | `sales` | Point-of-sale transaction records |
| 6 | `bills` | Generated bill headers with tax/discount |
| 7 | `purchase_orders` | B2B orders from admin to suppliers |
| 8 | `purchase_order_items` | Line items in each purchase order |
| 9 | `po_messages` | Real-time chat messages on PO threads |
| 10 | `customer_cart` | Customer shopping carts |
| 11 | `customer_addresses` | Saved delivery addresses |
| 12 | `customer_orders` | Customer e-commerce orders |
| 13 | `customer_order_items` | Snapshot of ordered items with price history |
| 14 | `customer_payments` | Payment records (COD/Card) |
| 15 | `wishlists` | Customer wishlisted medicines |

---

## 📡 API Documentation

Full API documentation with 22 endpoints is available in [API_DOCS.md](API_DOCS.md).

### Quick API Overview

| Module | Endpoint | Method | Description |
|--------|----------|--------|-------------|
| Auth | `/app/api/register.php` | POST | Register new user |
| Auth | `/app/api/login.php` | POST | User login |
| Auth | `/app/api/verify_otp.php` | POST | Verify/resend email OTP |
| Auth | `/app/api/logout.php` | GET | Logout |
| Admin | `/app/api/admin/po_api.php` | GET/POST | Purchase order management |
| Admin | `/app/api/admin/inventory_crud.php` | GET/POST | Medicine CRUD |
| Admin | `/app/api/admin/supplier_crud.php` | GET/POST | Supplier CRUD |
| Admin | `/app/api/admin/user_crud.php` | GET/POST | User CRUD |
| Admin | `/app/api/admin/analytics.php` | GET | Revenue & inventory charts |
| Pharmacist | `/app/api/pharmacist/dashboard_api.php` | GET | Dashboard statistics |
| Pharmacist | `/app/api/pharmacist/billing_api.php` | GET/POST | POS billing (FIFO) |
| Pharmacist | `/app/api/pharmacist/inventory_api.php` | GET/POST | Batch inventory |
| Pharmacist | `/app/api/pharmacist/alerts_api.php` | GET | Stock & expiry alerts |
| Customer | `/app/api/customer/shop_api.php` | GET | Browse medicines |
| Customer | `/app/api/customer/cart_api.php` | GET/POST | Shopping cart |
| Customer | `/app/api/customer/checkout_api.php` | POST | Place order |
| Customer | `/app/api/customer/orders_api.php` | GET | Order history |
| Customer | `/app/api/customer/profile_api.php` | GET/POST | Addresses & wishlist |
| Supplier | `/app/api/supplier/orders_api.php` | GET/POST | Manage purchase orders |
| Shared | `/app/api/shared/chat_api.php` | GET/POST | B2B order chat |
| Shared | `/app/api/notifications_api.php` | GET | Role-based notifications |
| AI | `/app/api/ai_chat.php` | POST | AI assistant chatbot |

---

## ⚙️ Installation & Local Setup

### Prerequisites
- PHP 8.0+ with PDO MySQL extension
- MySQL 5.7+ or TiDB
- Apache (XAMPP recommended for local development)
- Git

### Steps

1. **Clone the repository:**
```bash
git clone https://github.com/Saadulhassan0/roobi-medin.git
cd roobi-medin
```

2. **Configure environment:**
```bash
cp .env.example .env
# Edit .env with your database credentials
```

3. **Set up database:**
- Import `database/schema.sql` into your MySQL database, OR
- Open `http://localhost/roobi-medin/setup_db.php` in your browser
- Then open `http://localhost/roobi-medin/update_db.php` for additional tables

4. **Run locally with XAMPP:**
- Place the project folder in `C:\xampp\htdocs\roobi-medin`
- Start Apache and MySQL from XAMPP Control Panel
- Open `http://localhost/roobi-medin/public/index.html`

---

## ☁️ Cloud Deployment

### Backend (Render)
- Deployed using **Docker** container (PHP 8.2 + Apache)
- Auto-deploys on every `git push` to `main` branch
- Environment variables configured in Render Dashboard

### Frontend (Vercel)
- Static frontend served via Vercel
- API requests are proxied to Render backend via `vercel.json` rewrites

### Database (TiDB Serverless)
- MySQL-compatible cloud database
- Forced SSL/TLS connections
- Free tier with automatic scaling

---

## 🔒 Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | Database hostname | `gateway01.us-east-1.prod.aws.tidbcloud.com` |
| `DB_PORT` | Database port | `4000` |
| `DB_NAME` | Database name | `roobimed` |
| `DB_USER` | Database username | `your_username` |
| `DB_PASS` | Database password | `your_password` |

> ⚠️ **Never commit real credentials.** Use `.env.example` as a template.

---

## 👤 Author

**Saadul Hassan**  
GitHub: [@Saadulhassan0](https://github.com/Saadulhassan0)

---

## 📄 License

This project is developed for educational and academic purposes.
