-- Create database tables
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_kes DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    stock INT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price_kes DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    specialization VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    doctor_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

CREATE TABLE resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content TEXT NOT NULL,
    category VARCHAR(100),
    file_url VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert sample data
INSERT INTO products (name, description, price_kes, category, stock, image) VALUES
('Knee Support Brace', 'High-quality knee support for injury recovery and prevention', 3500.00, 'Braces', 50, 'assets/images/knee-brace.svg'),
('Orthopedic Walking Boot', 'Medical boot for foot and ankle injury recovery', 8500.00, 'Footwear', 30, 'assets/images/walking-boot.svg');

-- Create admin user (password: admin123)
INSERT INTO users (email, password, name, is_admin) VALUES
('admin@cherishortho.com', '$2y$10$8KzO7O5I5zqb5Yl5nxkfzOgqV5Q5Y5XY5Y5Y5Y5Y5Y5Y5Y5Y5Y', 'Admin User', TRUE);

-- Insert sample doctors
INSERT INTO doctors (name, specialization, email) VALUES
('Dr. John Smith', 'Orthopedic Surgeon', 'john.smith@cherishortho.com'),
('Dr. Sarah Johnson', 'Sports Medicine', 'sarah.johnson@cherishortho.com');

-- Insert sample resources
INSERT INTO resources (title, description, content, category, created_by) VALUES
('Post-Surgery Recovery Guide', 'Comprehensive guide for recovery after orthopedic surgery', 'Detailed content about post-surgery recovery steps...', 'Recovery', 1),
('Exercise Routines for Knee Rehabilitation', 'Safe exercises for knee injury recovery', 'Step-by-step exercise instructions...', 'Rehabilitation', 1);
