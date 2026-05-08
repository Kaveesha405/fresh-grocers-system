# Fresh Grocers – Web-Based Grocery Ordering & Delivery System

A fully implemented web-based grocery ordering and delivery platform developed for **Fresh Grocers**, a Sri Lankan grocery delivery company that has been operating for over fifteen years. The company was running entirely on a manual, phone-based order handling process and required a complete digital transformation. This project was completed as part of the **Pearson BTEC HND in Computing — Unit 35: Systems Analysis & Design** assignment.

---

## Project Overview

Fresh Grocers had no digital infrastructure — customers placed orders by phone, delivery agents were assigned manually without considering location, and there was no feedback mechanism. The proposed system replaces this with a centralized web platform that handles customer orders, CSR-assisted offline orders, delivery agent management, automated SMS notifications, location-based agent assignment, and post-delivery ratings.

The system was developed using the **Agile Scrum** methodology, selected over traditional models (Waterfall, V-Model, Spiral) due to the iterative nature of the requirements and the need for continuous user feedback throughout development.

---

## System Requirements

- Customers and delivery agents can register online and receive login credentials via email
- Customers can browse the product catalogue and place orders online
- Customers receive an **SMS** after order confirmation with the delivery agent's contact number and ETA
- Customers can **rate** their delivery agent after a completed order
- A **CSR** can manually enter orders on behalf of phone-in customers; the customer receives an SMS to their provided number
- The system identifies **nearby available delivery agents** for agent selection
- Delivery agents can manually **update their GPS location** within the system

---

## User Roles

**Customer** — Register, browse products, manage cart, checkout, track orders, rate delivery agents

**CSR (Customer Service Representative)** — Log in to the CSR portal, manually enter orders for offline customers, trigger SMS confirmation

**Delivery Agent** — Log in, view assigned orders, update location, manage delivery status

**Admin** — Manage products, view all orders, access sales and performance reports, manage users

---

## Technology Stack

| Layer             | Technology                                                  |
|-------------------|-------------------------------------------------------------|
| Backend           | PHP 8                                                       |
| Frontend          | HTML5, CSS3, JavaScript                                     |
| Database          | MySQL (via phpMyAdmin)                                      |
| Database Access   | MySQLi (OOP)                                                |
| Security          | Input sanitization, `password_hash()` / `password_verify()`|
| Agent Routing     | Haversine Formula using stored GPS coordinates              |
| SMS               | Simulated SMS gateway integration                           |
| UI Design         | Figma                                                       |
| Diagramming       | Draw.io / Visual Paradigm                                   |
| Code Editor       | Visual Studio Code                                          |
| Version Control   | Git & GitHub                                                |

---

## Database Tables

Database name: `fresh_grocers`

| Table           | Description                                                                                         |
|-----------------|-----------------------------------------------------------------------------------------------------|
| `admin`         | Admin account credentials for dashboard access                                                      |
| `cart`          | Active shopping cart sessions per customer                                                          |
| `cartitem`      | Individual product lines stored within a cart (product, quantity)                                   |
| `csr`           | CSR account credentials and profile details                                                         |
| `customer`      | Registered customer accounts (name, email, hashed password, phone, address)                        |
| `deliveryagent` | Delivery agent profiles including GPS coordinates, active status flag, and workload count           |
| `message`       | Customer inquiry and messaging records                                                              |
| `order`         | All orders — includes `PlacedByCsr` Boolean flag and guest customer name/phone for offline orders   |
| `orderitem`     | Individual product lines per order (product, quantity, unit price)                                  |
| `payment`       | Payment records linked to orders (amount, method, status: Pending / Paid)                           |
| `product`       | Product catalogue (name, description, price in LKR, category, stock quantity)                      |
| `rating`        | Customer ratings and feedback comments submitted for delivery agents after delivery completion      |

---

## Project Structure

```
fresh-grocers/
├── admin/              # Admin dashboard pages
├── api/                # Backend API endpoints (AJAX handlers)
├── assets/             # CSS, JS, and image files
├── csr/                # CSR portal pages
├── customer/           # Customer-facing pages
├── delivery/           # Delivery agent portal pages
├── includes/           # Shared PHP includes (header, footer, session checks)
├── config.php          # Database connection and session initialization
├── index.php           # Application entry point / home page
├── fresh_grocers.sql   # Full database schema and sample data
└── README.md
```

---

## Getting Started

### Prerequisites

- PHP 8.0 or later
- MySQL 5.7 or later
- XAMPP or WAMP (local server environment)

### Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/fresh-grocers.git
   ```

2. Import the database:
   - Open **phpMyAdmin**
   - Create a new database named `fresh_grocers`
   - Import `fresh_grocers.sql`

3. Configure the connection:
   - Open `config.php`
   - Update your MySQL host, username, and password

4. Launch the application:
   - Place the project folder inside `htdocs` (XAMPP) or `www` (WAMP)
   - Visit `http://localhost/fresh-grocers`

---

## Key Implementation Highlights

- **`config.php`** — Handles MySQLi database connection, PHP session initialization, and global input sanitization to prevent SQL Injection and XSS attacks
- **User Authentication** — Passwords stored using `password_hash()` at registration; verified with `password_verify()` at login; role-based sessions control access per portal
- **Nearest-Agent Algorithm** — Haversine Formula applied against GPS coordinates stored in the `deliveryagent` table to rank available agents by proximity to the customer
- **CSR Order Entry** — The `order` table has a `PlacedByCsr` Boolean flag; CSR-entered orders store guest customer name and phone without needing a registered account
- **Cart & Checkout** — `cart` and `cartitem` tables manage the active shopping session; on checkout, stock is reduced in `product`, `order` and `orderitem` records are created, a `payment` record is inserted, and SMS is simulated
- **Agent Location Update** — Delivery agents manually update their latitude/longitude in the `deliveryagent` table via the delivery portal

---

## Methodology

**Agile Scrum** was chosen over Waterfall, V-Model, and Spiral because Fresh Grocers' requirements — especially location-based agent routing and SMS integration — needed early prototyping and continuous stakeholder feedback. Sprint-based delivery allowed each user role portal to be built, tested, and reviewed incrementally before the next phase began.

---

## Author

**K.D. Kaveesha Amiru Nimnaka Fernando** | Student ID: 00272845  
Pearson BTEC HND in Computing — Unit 35: System Analysis & Design  

> This project was developed for academic purposes.
