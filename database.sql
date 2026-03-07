-- BLACK CLOTHES Database Schema
CREATE DATABASE IF NOT EXISTS black_clothes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE black_clothes;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer','admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    type ENUM('wholesale','individual','both') DEFAULT 'both'
);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    material ENUM('cotton','polyester','mixed') NOT NULL,
    category_id INT,
    sale_type ENUM('wholesale','individual','both') DEFAULT 'both',
    price_individual DECIMAL(10,2),
    price_wholesale DECIMAL(10,2),
    min_wholesale_qty INT DEFAULT 12,
    stock INT DEFAULT 0,
    featured TINYINT(1) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Product images (multiple per product for 360 view)
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    angle INT DEFAULT 0,
    is_main TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product sizes/variants
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size ENUM('XS','S','M','L','XL','XXL','XXXL') NOT NULL,
    color VARCHAR(50) DEFAULT 'Negro',
    stock INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    order_type ENUM('individual','wholesale') DEFAULT 'individual',
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    shipping_name VARCHAR(150),
    shipping_address TEXT,
    shipping_phone VARCHAR(20),
    notes TEXT,
    telegram_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT,
    product_name VARCHAR(200),
    size VARCHAR(10),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Cart (persistent)
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    user_id INT,
    product_id INT NOT NULL,
    variant_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert default admin
INSERT INTO users (name, email, password, role) VALUES 
('Admin Black Clothes', 'admin@blackclothes.mx', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiIoFKJhIxBZ3GnMJ3RLRX', 'admin');

-- Insert categories
INSERT INTO categories (name, slug, type) VALUES
('Playeras Básicas', 'playeras-basicas', 'both'),
('Playeras Premium', 'playeras-premium', 'individual'),
('Paquetes Mayoreo', 'paquetes-mayoreo', 'wholesale');

-- Insert sample products
INSERT INTO products (name, slug, description, material, category_id, sale_type, price_individual, price_wholesale, min_wholesale_qty, stock, featured) VALUES
('Playera Negra Algodón Classic', 'playera-algodon-classic', 'Playera negra 100% algodón peinado, corte regular. Suave al tacto, perfecta para el uso diario.', 'cotton', 1, 'both', 299.00, 199.00, 12, 500, 1),
('Playera Negra Premium Algodón', 'playera-algodon-premium', 'Playera negra algodón supima 180gsm. Corte slim fit. Calidad superior para los más exigentes.', 'cotton', 2, 'individual', 449.00, 320.00, 6, 200, 1),
('Playera Negra Poliéster Sport', 'playera-poliester-sport', 'Playera técnica de poliéster 100%. Transpirable, ideal para deportes y actividad física.', 'polyester', 1, 'both', 249.00, 159.00, 12, 800, 1),
('Playera Poliéster Dry-Fit', 'playera-poliester-dryfit', 'Tecnología dry-fit de poliéster reciclado. Máxima absorción de humedad.', 'polyester', 1, 'both', 319.00, 219.00, 12, 600, 0),
('Playera Algodón Oversized', 'playera-algodon-oversized', 'Corte oversized, 100% algodón. El estilo urbano en su máxima expresión.', 'cotton', 2, 'individual', 389.00, 269.00, 6, 300, 1),
('Pack Mayoreo Algodón x12', 'pack-mayoreo-algodon-12', 'Paquete de 12 playeras negras de algodón. Surtido de tallas. Precio por pieza.', 'cotton', 3, 'wholesale', NULL, 175.00, 12, 1000, 1),
('Pack Mayoreo Poliéster x12', 'pack-mayoreo-poliester-12', 'Paquete de 12 playeras negras de poliéster sport. Ideal para uniformes y equipos.', 'polyester', 3, 'wholesale', NULL, 149.00, 12, 1200, 1),
('Playera Algodón Cuello V', 'playera-algodon-cuello-v', 'Playera cuello en V, algodón suave 160gsm. Elegante y versátil.', 'cotton', 2, 'individual', 329.00, 239.00, 6, 400, 0);

-- Insert variants for products
INSERT INTO product_variants (product_id, size, stock) VALUES
(1,'S',80),(1,'M',120),(1,'L',150),(1,'XL',100),(1,'XXL',50),
(2,'S',30),(2,'M',60),(2,'L',70),(2,'XL',40),
(3,'S',100),(3,'M',200),(3,'L',250),(3,'XL',150),(3,'XXL',100),
(4,'S',80),(4,'M',160),(4,'L',200),(4,'XL',120),(4,'XXL',80),
(5,'S',50),(5,'M',80),(5,'L',100),(5,'XL',70),
(8,'S',60),(8,'M',100),(8,'L',120),(8,'XL',90),(8,'XXL',30);

-- Insert product images (placeholder paths - admin will upload real ones)
INSERT INTO product_images (product_id, image_path, angle, is_main, sort_order) VALUES
(1, 'placeholder_front.jpg', 0, 1, 0),
(1, 'placeholder_back.jpg', 180, 0, 1),
(2, 'placeholder_front.jpg', 0, 1, 0),
(3, 'placeholder_front.jpg', 0, 1, 0),
(4, 'placeholder_front.jpg', 0, 1, 0),
(5, 'placeholder_front.jpg', 0, 1, 0),
(6, 'placeholder_front.jpg', 0, 1, 0),
(7, 'placeholder_front.jpg', 0, 1, 0),
(8, 'placeholder_front.jpg', 0, 1, 0);
