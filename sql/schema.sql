CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name ENUM('admin','staff','kitchen') NOT NULL UNIQUE
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,
    category VARCHAR(50),
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    stock_item_id INT UNSIGNED DEFAULT NULL,
    deduct_qty DECIMAL(8,3) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('dine-in','takeaway') NOT NULL,
    table_number VARCHAR(10),
    status ENUM('Received','Preparing','Ready','Paid') NOT NULL DEFAULT 'Received',
    staff_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(id),
    INDEX idx_orders_created_at (created_at),
    INDEX idx_orders_status (status)
);

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_menu_item_id (menu_item_id)
);

CREATE TABLE stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    minimum_threshold INT UNSIGNED NOT NULL DEFAULT 10,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    method ENUM('cash','card','digital') NOT NULL,
    staff_id INT UNSIGNED NOT NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (staff_id) REFERENCES users(id),
    INDEX idx_payments_order_id (order_id)
);

CREATE TABLE restaurant_tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(20) NOT NULL UNIQUE,
    seats TINYINT UNSIGNED NOT NULL DEFAULT 4,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO roles (name) VALUES ('admin'), ('staff'), ('kitchen');

INSERT INTO users (name, email, phone, password_hash, role_id, status) VALUES
('Jenish Dhimal', 'jenish@bistro.com', '0412000001', '$2y$10$89Hz4XxnPa8zOlWABcSQYuUM0D4McKJkNIXAgzMCTFV25y.Fi0F6e', 1, 'active'),

('Sujan BK', 'sujan@bistro.com', '0412000002', '$2y$10$U7FPfBOS.YQsOtfp5jffcuYB/FridBHXoRE8/7412ys/F0a3/CAFG', 2, 'active'),

('Aasthik Kunwar', 'aasthik@bistro.com', '0412000003', '$2y$10$OmXZOiLz.FBCk1ewpGZ77eOn5saqTbIirEOg8sJlJsRP5ZtUS3cBO', 3, 'active');


INSERT INTO menu_items (name, description, price, category, stock_item_id, deduct_qty) VALUES
('Grilled Chicken', 'Herb-marinated chicken breast, served with seasonal veg', 18.50, 'Mains', 1, 0.250),
('Beef Burger', 'Angus beef patty, brioche bun, house sauce', 16.00, 'Mains', 2, 0.200),
('Margherita Pizza', 'San Marzano tomato, fresh mozzarella, basil', 17.50, 'Mains', 3, 1.000),
('Caesar Salad', 'Cos lettuce, croutons, parmesan, Caesar dressing', 13.00, 'Starters', NULL, 0),
('Spring Rolls', 'Crispy vegetable rolls, sweet chilli dipping sauce', 9.50, 'Starters', NULL, 0),
('Chocolate Lava Cake', 'Warm chocolate fondant, vanilla bean ice cream', 11.00, 'Desserts', NULL, 0),
('Lemonade', 'Fresh-squeezed lemonade, mint, ice', 5.50, 'Drinks', NULL, 0),
('Flat White', 'Double shot espresso, steamed whole milk', 4.50, 'Drinks', NULL, 0);

INSERT INTO orders (id, type, table_number, status, staff_id, created_at) VALUES
(1001, 'dine-in', 'Table 5', 'Preparing', 2, NOW() - INTERVAL 2 MINUTE),
(1002, 'dine-in', 'Table 2', 'Ready', 3, NOW() - INTERVAL 8 MINUTE),
(1003, 'takeaway', NULL, 'Paid', 2, NOW() - INTERVAL 15 MINUTE),
(1004, 'dine-in', 'Table 9', 'Paid', 3, NOW() - INTERVAL 22 MINUTE),
(1005, 'dine-in', 'Table 1', 'Received', 2, NOW() - INTERVAL 1 MINUTE);

INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES
(1001, 1, 2, 18.50), (1001, 7, 2, 5.50),
(1002, 2, 1, 16.00), (1002, 4, 1, 13.00),
(1003, 3, 1, 17.50), (1003, 5, 2, 9.50), (1003, 8, 1, 4.50),
(1004, 1, 2, 18.50), (1004, 6, 2, 11.00), (1004, 7, 4, 5.50), (1004, 8, 2, 4.50),
(1005, 2, 2, 16.00), (1005, 5, 1, 9.50);

INSERT INTO restaurant_tables (label, seats) VALUES
('Table 1',4),('Table 2',4),('Table 3',4),('Table 4',4),('Table 5',4),
('Table 6',4),('Table 7',4),('Table 8',4),('Table 9',4),('Table 10',4),
('Table 11',4),('Table 12',4),('Table 13',4),('Table 14',4),('Table 15',4),
('Table 16',4),('Table 17',4),('Table 18',4),('Table 19',4),('Table 20',4);

INSERT INTO stock (item_name, quantity, minimum_threshold) VALUES
('Chicken Breast (kg)', 12, 5),
('Ground Beef (kg)', 3, 5),
('Pizza Dough (units)', 20, 10);

INSERT INTO payments (order_id, amount, method, staff_id, paid_at) VALUES
(1003, 41.00, 'card', 2, NOW() - INTERVAL 14 MINUTE),
(1004, 87.00, 'cash', 3, NOW() - INTERVAL 20 MINUTE);

SET FOREIGN_KEY_CHECKS = 1;
