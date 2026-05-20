-- ============================================
-- FILE: database/schema.sql
-- ============================================
CREATE DATABASE IF NOT EXISTS marketplace_db;
USE marketplace_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Cart table (for persistent cart - optional, we'll use session for simplicity)
-- For persistent cart across devices, uncomment below
/*
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(100),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (session_id)
);
*/

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_time DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@example.com', '$2y$10$YourHashedPasswordHere', 'admin'),
('John Doe', 'john@example.com', '$2y$10$YourHashedPasswordHere', 'customer');

-- Insert categories
INSERT INTO categories (name, slug, description) VALUES
('Electronics', 'electronics', 'Gadgets and electronic devices'),
('Clothing', 'clothing', 'Fashion and apparel'),
('Books', 'books', 'Books and publications'),
('Home & Garden', 'home-garden', 'Home decor and gardening tools');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, stock, image_url) VALUES
(1, 'Smartphone X', 'Latest smartphone with amazing features', 699.99, 50, 'https://via.placeholder.com/300x200?text=Smartphone'),
(1, 'Laptop Pro', 'High performance laptop for professionals', 1299.99, 30, 'https://via.placeholder.com/300x200?text=Laptop'),
(2, 'Cotton T-Shirt', 'Comfortable 100% cotton t-shirt', 19.99, 100, 'https://via.placeholder.com/300x200?text=TShirt'),
(2, 'Jeans', 'Classic blue jeans', 49.99, 75, 'https://via.placeholder.com/300x200?text=Jeans'),
(3, 'PHP Programming', 'Learn PHP from scratch', 39.99, 20, 'https://via.placeholder.com/300x200?text=PHP+Book');
