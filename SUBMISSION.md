# 📦 MedIn AI Pharmacy — Final Submission

---

## 1. 🔗 GitHub Repository

**Repository URL:**  
👉 https://github.com/Saadulhassan0/roobi-medin

**Contains:**
- ✅ Frontend (HTML/CSS/JS in `/public/`)
- ✅ Backend (PHP APIs in `/app/api/`)
- ✅ README.md (with features, tech stack, installation steps, live URLs)
- ✅ API_DOCS.md (complete API documentation — 22 endpoints)
- ✅ .env.example (environment variable template)
- ✅ Dockerfile + render.yaml + vercel.json (deployment configs)
- ✅ Database schema (`/database/schema.sql`)
- ✅ Migration scripts (setup_db.php, update_db.php, migrate_ecommerce.php)

---

## 2. 🌐 Live Frontend Link (Vercel)

👉 https://roobi-medin.vercel.app

---

## 3. ⚙️ Live Backend Link (Render)

👉 https://roobi-backend.onrender.com

---

## 4. 🗄️ Database Details

| Field | Value |
|-------|-------|
| **Provider** | TiDB Serverless (MySQL-compatible Cloud DB) |
| **Database Name** | `roobimed` |
| **Port** | `4000` |
| **Connection** | SSL/TLS Encrypted |

### Database Tables (15 Total):

| # | Table Name | Description |
|---|-----------|-------------|
| 1 | `users` | All system users (admin, pharmacist, supplier, customer) with auth fields |
| 2 | `suppliers` | Registered supplier companies |
| 3 | `medicines` | Master medicine catalog with pricing and expiry |
| 4 | `medicine_batches` | Batch-level inventory with FIFO tracking and status |
| 5 | `sales` | Point-of-sale transaction records |
| 6 | `bills` | Generated bill headers with subtotal, tax, discount |
| 7 | `purchase_orders` | B2B orders from admin to suppliers |
| 8 | `purchase_order_items` | Line items within each purchase order |
| 9 | `po_messages` | Real-time chat messages on purchase order threads |
| 10 | `customer_cart` | Customer shopping cart items |
| 11 | `customer_addresses` | Saved delivery addresses (home, work, other) |
| 12 | `customer_orders` | E-commerce customer orders |
| 13 | `customer_order_items` | Snapshot of ordered items with price history |
| 14 | `customer_payments` | Payment records (COD / Card) |
| 15 | `wishlists` | Customer wishlisted medicines |

---

## 5. 🔐 Environment Variables

File: `.env.example` (included in repo)

```env
# Database Connection (TiDB Serverless / MySQL)
DB_HOST=your-cloud-database-host.tidbcloud.com
DB_PORT=4000
DB_NAME=roobimed
DB_USER=your-database-username
DB_PASS=your-database-password

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://roobi-backend.onrender.com
FRONTEND_URL=https://roobi-medin.vercel.app
```

> ⚠️ Real credentials are NOT included. Only the template is shared.

---

## 6. 📡 API Documentation

**Full documentation:** [API_DOCS.md](https://github.com/Saadulhassan0/roobi-medin/blob/main/API_DOCS.md)

### Quick API Summary (22 Endpoints):

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/app/api/register.php` | Register new user |
| `POST` | `/app/api/login.php` | User login |
| `POST` | `/app/api/verify_otp.php` | Verify/resend email OTP |
| `GET` | `/app/api/logout.php` | Logout |
| `GET/POST` | `/app/api/admin/po_api.php` | Purchase orders (create, approve, reject) |
| `GET/POST` | `/app/api/admin/inventory_crud.php` | Medicine CRUD + remove expired |
| `GET/POST` | `/app/api/admin/supplier_crud.php` | Supplier CRUD |
| `GET/POST` | `/app/api/admin/user_crud.php` | User CRUD |
| `GET` | `/app/api/admin/analytics.php` | Revenue & inventory analytics |
| `GET` | `/app/api/pharmacist/dashboard_api.php` | Dashboard KPIs |
| `GET/POST` | `/app/api/pharmacist/billing_api.php` | POS billing with FIFO |
| `GET/POST` | `/app/api/pharmacist/inventory_api.php` | Batch inventory management |
| `GET` | `/app/api/pharmacist/alerts_api.php` | Low stock & expiry alerts |
| `GET` | `/app/api/customer/shop_api.php` | Browse & search medicines |
| `GET/POST` | `/app/api/customer/cart_api.php` | Shopping cart |
| `POST` | `/app/api/customer/checkout_api.php` | Checkout with FIFO deduction |
| `GET` | `/app/api/customer/orders_api.php` | Order history & details |
| `GET/POST` | `/app/api/customer/profile_api.php` | Addresses & wishlist |
| `GET/POST` | `/app/api/supplier/orders_api.php` | View/accept/ship orders |
| `GET/POST` | `/app/api/shared/chat_api.php` | B2B order chat |
| `GET` | `/app/api/notifications_api.php` | Role-based notifications |
| `POST` | `/app/api/ai_chat.php` | AI assistant chatbot |

---

## 7. 📖 README File

✅ Included in the repository root: [README.md](https://github.com/Saadulhassan0/roobi-medin/blob/main/README.md)

**README Contains:**
- ✅ Project name & badges
- ✅ Features list (by role)
- ✅ Tech stack table
- ✅ Full project structure tree
- ✅ Database schema (15 tables)
- ✅ API documentation summary (22 endpoints)
- ✅ Installation & local setup steps
- ✅ Cloud deployment guide
- ✅ Environment variables reference
- ✅ Live Frontend URL (Vercel)
- ✅ Live Backend URL (Render)
- ✅ Default admin login credentials

---

## 8. 🎥 Demo Video

> **TODO:** Record a short demo video showing:
> 1. Login as Admin → Dashboard → Create Purchase Order
> 2. Login as Supplier → Accept & Ship order
> 3. Login as Admin → Approve delivery → Inventory updated
> 4. Login as Customer → Browse shop → Add to cart → Checkout
> 5. Login as Pharmacist → POS billing → Generate bill
> 6. Show database tables in TiDB dashboard
>
> Upload to **Google Drive** or **YouTube (Unlisted)** and paste the link below.
>
> **Demo Video URL:** `[Paste your link here after recording]`

---

## 📋 Submission Summary

| Item | Status | Link |
|------|--------|------|
| GitHub Repository | ✅ Complete | https://github.com/Saadulhassan0/roobi-medin |
| Frontend (Vercel) | ✅ Live | https://roobi-medin.vercel.app |
| Backend (Render) | ✅ Live | https://roobi-backend.onrender.com |
| Database | ✅ TiDB Serverless | `roobimed` (15 tables) |
| .env.example | ✅ In Repo | [.env.example](https://github.com/Saadulhassan0/roobi-medin/blob/main/.env.example) |
| API Documentation | ✅ In Repo | [API_DOCS.md](https://github.com/Saadulhassan0/roobi-medin/blob/main/API_DOCS.md) |
| README | ✅ In Repo | [README.md](https://github.com/Saadulhassan0/roobi-medin/blob/main/README.md) |
| Demo Video | ⏳ Pending | Record & upload |

---

**Project by: Saadul Hassan**  
**GitHub: [@Saadulhassan0](https://github.com/Saadulhassan0)**
