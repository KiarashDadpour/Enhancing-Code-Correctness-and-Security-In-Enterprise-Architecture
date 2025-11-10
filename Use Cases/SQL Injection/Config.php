
<?php
class DatabaseConfig {
    private $host = '127.0.0.1';
    private $port = 3307;
    private $user = 'root';
    private $pass = '';
    private $db_name = 'terminal_db';
    
    public function connect() {
        // Connect to MySQL
        $conn = new mysqli($this->host, $this->user, $this->pass, '', $this->port);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Drop and recreate database to ensure clean start
        $conn->query("DROP DATABASE IF EXISTS {$this->db_name}");
        $conn->query("CREATE DATABASE {$this->db_name}");
        $conn->select_db($this->db_name);
        
        // Create tables
        $this->createTables($conn);
        
        // Insert sample data - ONLY ONCE
        $this->insertSampleData($conn);
        
        $conn->close();
        
        // Return new connection
        return new mysqli($this->host, $this->user, $this->pass, $this->db_name, $this->port);
    }
    
    private function createTables($conn) {
        $conn->query("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE,
            password VARCHAR(100),
            role VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $conn->query("CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            category VARCHAR(50),
            price DECIMAL(10,2),
            quantity INT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    private function insertSampleData($conn) {
        // Insert users - ONLY ONCE
        $conn->query("INSERT INTO users (username, password, role) VALUES 
            ('admin', 'admin123', 'administrator'),
            ('user', 'pass123', 'user')");
        
        // Insert EXACTLY 10 products - ONLY ONCE
        $conn->query("INSERT INTO products (name, category, price, quantity, description) VALUES 
            ('iPhone 15 Pro', 'Electronics', 999.99, 50, 'Latest Apple smartphone'),
            ('Samsung Galaxy S24', 'Electronics', 899.99, 75, 'Android flagship phone'),
            ('MacBook Pro 16\"', 'Computers', 2399.99, 25, 'Professional laptop'),
            ('Dell XPS 13', 'Computers', 1299.99, 40, 'Ultrabook laptop'),
            ('Sony WH-1000XM5', 'Audio', 349.99, 100, 'Noise cancelling headphones'),
            ('Apple AirPods Pro', 'Audio', 249.99, 150, 'Wireless earbuds'),
            ('iPad Air', 'Tablets', 599.99, 60, 'Tablet computer'),
            ('Samsung Tab S9', 'Tablets', 799.99, 45, 'Android tablet'),
            ('Nintendo Switch', 'Gaming', 299.99, 80, 'Gaming console'),
            ('PlayStation 5', 'Gaming', 499.99, 30, 'Next-gen console')");
    }
}
?>
