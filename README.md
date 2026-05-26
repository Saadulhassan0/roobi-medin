# MedIn AI Pharmacy Management System

Welcome to the **MedIn AI Pharmacy Management System**, a fully cloud-native, responsive, and secure platform designed to handle B2B (Supplier) and B2C (Customer) pharmacy operations.

## Live Application
- **Frontend URL:** [Your Vercel Link here]
- **Backend URL:** [Your Render Link here]

## How to Log In
The system supports 4 different roles: **Admin**, **Pharmacist**, **Supplier**, and **Customer**.

### Super Admin Credentials
You can log in to the main dashboard using the default admin account:
- **Email:** `admin@medin.com`
- **Password:** `Admin@123`

---

## Step-by-Step Guide: How to Order Medicine (Admin -> Supplier)

The system features a robust **B2B Purchase Order (PO) System** that allows the Admin to order medicine directly from registered Suppliers.

### Step 1: Create a Purchase Order (Admin)
1. **Log in** using the Super Admin credentials.
2. On the left sidebar, click on **Purchase Orders**.
3. Click the **"Create New PO"** button in the top right.
4. **Select a Supplier** from the dropdown list.
5. **Add Medicines**: Select a medicine, enter the quantity you want to order, and click **"Add"**. You can add multiple medicines to a single order.
6. Choose a **Due Date** and **Delivery Location** (e.g., Main Warehouse).
7. Click **"Send Purchase Order"**. The order status will initially be **Pending**.

### Step 2: Accept & Ship the Order (Supplier)
1. Log out of the Admin account.
2. Register a new account and choose **Supplier** as your role, OR log in to an existing Supplier account.
3. On the Supplier sidebar, click on **Purchase Orders**.
4. You will see the new order sent by the Admin.
5. Click **"Accept"** to acknowledge the order, and when it is ready, click **"Ship"**.

### Step 3: Approve Delivery (Admin)
1. Log back in as the **Admin**.
2. Go to **Purchase Orders**.
3. Once the Supplier has marked the order as "Shipped", you will see an **"Approve"** button next to it.
4. Click **"Approve"**. This will automatically update your central inventory with the newly arrived medicine!

### Bonus Feature: B2B Order Chat!
Every Purchase Order has a built-in real-time chat system. 
If an Admin clicks **"Chat"** on an order, they can send a message directly to the Supplier (e.g., *"Is this shipment arriving today?"*). The Supplier can reply from their dashboard!

---

## Other Core Functions

### 1. E-Commerce for Customers
- **Customers** can register an account, browse medicines, add them to a shopping cart, and place home delivery orders.
- Customers can also maintain their delivery addresses and order history.

### 2. Pharmacist Point of Sale (POS)
- **Pharmacists** can log in and access a fast, intuitive Point of Sale system to sell medicines over the counter to walk-in patients.
- The system automatically checks stock levels and prevents selling out-of-stock items.

### 3. Inventory Management & Expiry Tracking
- The system automatically tracks the exact quantity of every medicine.
- It provides alerts for low-stock medicines and medicines that are approaching their expiration date.

### 4. Live AI Assistant
- Click the floating **AI Assistant Icon** in the bottom corner to ask questions about the pharmacy data (e.g., "What medicines are low on stock?").

## Tech Stack
- **Frontend:** Vercel (HTML/CSS/JS)
- **Backend:** Render (PHP 8.2 / Apache via Docker)
- **Database:** TiDB Serverless Cloud Database (MySQL Compatible)
- **Security:** Forced SSL/TLS Database Connections, Password Hashing, Prepared SQL Statements.
