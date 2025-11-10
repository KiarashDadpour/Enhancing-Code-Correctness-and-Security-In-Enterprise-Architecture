<?php
require_once 'config.php';

class TerminalSystem {
    private $conn;
    private $current_user = null;
    private $is_admin = false;
    
    public function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        $db_config = new DatabaseConfig();
        $this->conn = $db_config->connect();
        
        if (!$this->conn) {
            die("❌ Cannot connect to database\n");
        }
    }
    
    public function run() {
        $this->clearScreen();
        $this->showLogin();
        
        if ($this->authenticate()) {
            $this->is_admin = ($this->current_user['role'] === 'administrator' || $this->current_user['role'] === 'admin');
            $this->mainTerminal();
        }
    }
    
    private function clearScreen() {
        system('cls');
    }
    
    private function showLogin() {
        echo "╔═══════════════════════════════════════╗\n";
        echo "║         DATABASE CONTROL PANEL        ║\n";
        echo "║        Authentication Required        ║\n";
        echo "╚═══════════════════════════════════════╝\n\n";
    }
    
    private function authenticate() {
        $attempts = 0;
        
        while ($attempts < 3) {
            echo "login: ";
            $username = trim(fgets(STDIN));
            echo "password: ";
            $password = trim(fgets(STDIN));
            
            $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
            $result = $this->conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $this->current_user = $result->fetch_assoc();
                echo "\n✅ Login successful! Welcome {$this->current_user['username']}\n";
                sleep(1);
                return true;
            }
            
            $attempts++;
            echo "❌ Login failed. Attempts remaining: " . (3 - $attempts) . "\n\n";
        }
        
        echo "🚫 Maximum login attempts exceeded.\n";
        return false;
    }
    
    private function mainTerminal() {
        $this->clearScreen();
        echo "=== DATABASE CONTROL TERMINAL ===\n";
        echo "User: {$this->current_user['username']} | Role: {$this->current_user['role']}\n";
        echo "Admin Access: " . ($this->is_admin ? "✅ YES" : "❌ NO") . "\n";
        echo "Type 'help' for commands\n";
        echo "Type 'exit' to logout\n\n";
        
        while (true) {
            echo "{$this->current_user['username']}@db-server:~$ ";
            $input = trim(fgets(STDIN));
            
            if (empty($input)) continue;
            
            $this->executeCommand($input);
            
            if ($input === 'exit' || $input === 'logout') {
                echo "Logging out...\n";
                break;
            }
        }
    }
    
    private function executeCommand($command) {
        $parts = explode(' ', $command);
        $cmd = strtolower($parts[0]);
        $param = isset($parts[1]) ? $parts[1] : '';
        
        switch ($cmd) {
            case 'help':
                $this->showHelp();
                break;
                
            case 'whoami':
                $this->showUserInfo();
                break;
                
            case 'users':
                $this->listUsers();
                break;
                
            case 'products':
                $this->listProducts();
                break;
                
            case 'search':
                $this->searchUser($param);
                break;
                
            case 'search_product':
                $this->searchProduct($param);
                break;
                
            case 'create_user':
                $this->createUser();
                break;
                
            case 'delete_user':
                $this->deleteUser($param);
                break;
                
            case 'tables':
                $this->listTables();
                break;
                
            case 'describe':
                $this->describeTable($param);
                break;
                
            case 'dump':
                $this->dumpDatabase();
                break;
                
            case 'backup':
                $this->backupDatabase();
                break;
                
            case 'restore':
                $this->restoreDatabase(implode(' ', array_slice($parts, 1)));
                break;
                
            case 'query':
                $this->executeQuery(implode(' ', array_slice($parts, 1)));
                break;
                
            case 'sql':
                $this->executeRawSQL(implode(' ', array_slice($parts, 1)));
                break;
                
            case 'privileges':
                $this->showPrivileges();
                break;
                
            case 'processlist':
                $this->showProcessList();
                break;
                
            case 'shutdown':
                $this->shutdownDatabase();
                break;
                
            case 'pwd':
                echo "/home/{$this->current_user['username']}\n";
                break;
                
            case 'ls':
                $this->listFiles();
                break;
                
            case 'clear':
                $this->clearScreen();
                break;
                
            case 'date':
                echo date('Y-m-d H:i:s') . "\n";
                break;
                
            case 'echo':
                echo implode(' ', array_slice($parts, 1)) . "\n";
                break;
                
            default:
                echo "Command not found: $command\n";
        }
    }
    
    private function showHelp() {
        echo "\n📋 Available Commands:\n";
        echo "=====================\n";
        
        echo "\n👥 USER MANAGEMENT:\n";
        echo "-------------------\n";
        echo "users              - List all users\n";
        echo "products           - List all products\n";
        echo "search <keyword>   - Search users\n";
        echo "search_product <k> - Search products\n";
        echo "create_user        - Create new user\n";
        echo "delete_user <id>   - Delete user (Admin only)\n";
        
        echo "\n🗄️ DATABASE OPERATIONS:\n";
        echo "---------------------\n";
        echo "tables             - Show all tables\n";
        echo "describe <table>   - Show table structure\n";
        echo "dump               - Export database dump\n";
        echo "backup             - Create database backup\n";
        echo "restore <file>     - Restore from backup\n";
        
        echo "\n⚡ ADVANCED COMMANDS:\n";
        echo "-------------------\n";
        echo "query <sql>        - Execute SQL query\n";
        echo "sql <sql>          - Execute raw SQL\n";
        echo "privileges         - Show user privileges\n";
        echo "processlist        - Show active connections\n";
        echo "shutdown           - Shutdown database (Admin only)\n";
        
        echo "\n🔧 SYSTEM COMMANDS:\n";
        echo "------------------\n";
        echo "help               - Show this help\n";
        echo "whoami             - Show current user info\n";
        echo "pwd                - Show current directory\n";
        echo "ls                 - List files\n";
        echo "clear              - Clear screen\n";
        echo "date               - Show date/time\n";
        echo "echo <text>        - Echo text\n";
        echo "exit               - Logout\n";
    }
    
    private function showUserInfo() {
        echo "\n👤 User Information:\n";
        echo "===================\n";
        echo "Username: {$this->current_user['username']}\n";
        echo "Role: {$this->current_user['role']}\n";
        echo "User ID: {$this->current_user['id']}\n";
        echo "Admin Access: " . ($this->is_admin ? "✅ YES" : "❌ NO") . "\n";
    }
    
    private function listUsers() {
        $result = $this->conn->query("SELECT id, username, role, created_at FROM users");
        
        if ($result && $result->num_rows > 0) {
            echo "\n👥 System Users:\n";
            echo str_repeat("=", 60) . "\n";
            printf("%-3s | %-15s | %-15s | %-20s\n", "ID", "Username", "Role", "Created");
            echo str_repeat("-", 60) . "\n";
            while ($row = $result->fetch_assoc()) {
                printf("%-3s | %-15s | %-15s | %-20s\n", 
                    $row['id'], $row['username'], $row['role'], $row['created_at']);
            }
            echo "Total: {$result->num_rows} users\n";
        } else {
            echo "No users found\n";
        }
    }
    
    private function listProducts() {
        $result = $this->conn->query("SELECT id, name, category, price, quantity FROM products");
        
        if ($result && $result->num_rows > 0) {
            echo "\n🛍️ Products Inventory:\n";
            echo str_repeat("=", 80) . "\n";
            printf("%-3s | %-20s | %-15s | %-10s | %-8s\n", "ID", "Name", "Category", "Price", "Qty");
            echo str_repeat("-", 80) . "\n";
            while ($row = $result->fetch_assoc()) {
                printf("%-3s | %-20s | %-15s | $%-9s | %-8s\n", 
                    $row['id'], $row['name'], $row['category'], $row['price'], $row['quantity']);
            }
            echo "Total: {$result->num_rows} products\n";
        } else {
            echo "No products found\n";
        }
    }
    
    private function searchUser($keyword) {
        if (empty($keyword)) {
            echo "Usage: search <keyword>\n";
            return;
        }
        
        $query = "SELECT id, username, password, role FROM users WHERE username LIKE '%$keyword%' OR role LIKE '%$keyword%'";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo "\n🔍 User Search Results:\n";
            echo str_repeat("=", 70) . "\n";
            printf("%-3s | %-15s | %-20s | %-15s\n", "ID", "Username", "Password", "Role");
            echo str_repeat("-", 70) . "\n";
            while ($row = $result->fetch_assoc()) {
                printf("%-3s | %-15s | %-20s | %-15s\n", 
                    $row['id'], $row['username'], $row['password'], $row['role']);
            }
            echo "Found: {$result->num_rows} result(s)\n";
        } else {
            echo "No results found\n";
        }
    }
    
    private function searchProduct($keyword) {
        if (empty($keyword)) {
            echo "Usage: search_product <keyword>\n";
            return;
        }
        
        $query = "SELECT id, name, category, price, quantity FROM products WHERE name LIKE '%$keyword%' OR category LIKE '%$keyword%'";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo "\n🔍 Product Search Results:\n";
            echo str_repeat("=", 80) . "\n";
            printf("%-3s | %-20s | %-15s | %-10s | %-8s\n", "ID", "Name", "Category", "Price", "Qty");
            echo str_repeat("-", 80) . "\n";
            while ($row = $result->fetch_assoc()) {
                printf("%-3s | %-20s | %-15s | $%-9s | %-8s\n", 
                    $row['id'], $row['name'], $row['category'], $row['price'], $row['quantity']);
            }
            echo "Found: {$result->num_rows} result(s)\n";
        } else {
            echo "No results found\n";
        }
    }
    
    private function createUser() {
        if (!$this->is_admin) {
            echo "❌ Permission denied. Admin access required.\n";
            return;
        }
        
        echo "Create New User:\n";
        echo "Username: ";
        $username = trim(fgets(STDIN));
        echo "Password: ";
        $password = trim(fgets(STDIN));
        echo "Role (admin/user): ";
        $role = trim(fgets(STDIN));
        
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        if ($this->conn->query($query)) {
            echo "✅ User created successfully!\n";
        } else {
            echo "❌ Error creating user: " . $this->conn->error . "\n";
        }
    }
    
    private function deleteUser($user_id) {
        if (!$this->is_admin) {
            echo "❌ Permission denied. Admin access required.\n";
            return;
        }
        
        if (empty($user_id)) {
            echo "Usage: delete_user <user_id>\n";
            return;
        }
        
        $query = "DELETE FROM users WHERE id = $user_id";
        if ($this->conn->query($query)) {
            echo "✅ User deleted successfully!\n";
        } else {
            echo "❌ Error deleting user: " . $this->conn->error . "\n";
        }
    }
    
    private function listTables() {
        $result = $this->conn->query("SHOW TABLES");
        
        if ($result && $result->num_rows > 0) {
            echo "\n🗄️ Database Tables:\n";
            echo str_repeat("=", 30) . "\n";
            $i = 1;
            while ($row = $result->fetch_array()) {
                echo "$i. " . $row[0] . "\n";
                $i++;
            }
            echo "Total: {$result->num_rows} tables\n";
        } else {
            echo "No tables found\n";
        }
    }
    
    private function describeTable($table_name) {
        if (empty($table_name)) {
            echo "Usage: describe <table_name>\n";
            return;
        }
        
        $result = $this->conn->query("DESCRIBE $table_name");
        
        if ($result && $result->num_rows > 0) {
            echo "\n📊 Table Structure: $table_name\n";
            echo str_repeat("=", 50) . "\n";
            printf("%-15s | %-15s | %-10s | %-5s\n", "Field", "Type", "Null", "Key");
            echo str_repeat("-", 50) . "\n";
            while ($row = $result->fetch_assoc()) {
                printf("%-15s | %-15s | %-10s | %-5s\n", 
                    $row['Field'], $row['Type'], $row['Null'], $row['Key']);
            }
        } else {
            echo "❌ Table not found or error: " . $this->conn->error . "\n";
        }
    }
    
    private function dumpDatabase() {
        $tables = [];
        $result = $this->conn->query("SHOW TABLES");
        
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $dump = "-- Database Dump - " . date('Y-m-d H:i:s') . "\n";
        $dump .= "-- Generated by: {$this->current_user['username']}\n\n";
        
        foreach ($tables as $table) {
            $dump .= "--\n-- Table structure for table `$table`\n--\n";
            $dump .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $create_result = $this->conn->query("SHOW CREATE TABLE $table");
            $create_row = $create_result->fetch_array();
            $dump .= $create_row[1] . ";\n\n";
            
            $dump .= "--\n-- Dumping data for table `$table`\n--\n";
            $data_result = $this->conn->query("SELECT * FROM $table");
            
            while ($data_row = $data_result->fetch_assoc()) {
                $columns = implode("`, `", array_keys($data_row));
                $values = implode("', '", array_map([$this->conn, 'real_escape_string'], array_values($data_row)));
                $dump .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
            }
            $dump .= "\n";
        }
        
        $filename = "dump_" . date('Y-m-d_H-i-s') . ".sql";
        file_put_contents($filename, $dump);
        echo "✅ Database dumped to: $filename\n";
    }
    
    private function backupDatabase() {
        if (!$this->is_admin) {
            echo "❌ Permission denied. Admin access required.\n";
            return;
        }
        
        $backup_file = "backup_" . date('Y-m-d_H-i-s') . ".sql";
        $command = "C:\\xampp\\mysql\\bin\\mysqldump -u root -P 3307 terminal_db > $backup_file";
        system($command);
        echo "✅ Database backup created: $backup_file\n";
    }
    
    private function restoreDatabase($filename) {
        if (!$this->is_admin) {
            echo "❌ Permission denied. Admin access required.\n";
            return;
        }
        
        if (!file_exists($filename)) {
            echo "❌ Backup file not found: $filename\n";
            return;
        }
        
        echo "⚠️  WARNING: This will overwrite current database! Continue? (y/n): ";
        $confirm = trim(fgets(STDIN));
        
        if (strtolower($confirm) === 'y') {
            $command = "C:\\xampp\\mysql\\bin\\mysql -u root -P 3307 terminal_db < $filename";
            system($command);
            echo "✅ Database restored from: $filename\n";
        } else {
            echo "❌ Restore cancelled.\n";
        }
    }
    
    private function executeQuery($query) {
        if (empty($query)) {
            echo "Usage: query <sql_query>\n";
            return;
        }
        
        $result = $this->conn->query($query);
        
        if ($result === TRUE) {
            echo "✅ Query executed successfully\n";
            if ($this->conn->affected_rows > 0) {
                echo "📊 Rows affected: " . $this->conn->affected_rows . "\n";
            }
        } else if ($result && $result->num_rows > 0) {
            $this->displayResults($result);
        } else {
            echo "❌ No results or error in query: " . $this->conn->error . "\n";
        }
    }
    
    private function executeRawSQL($query) {
        if (empty($query)) {
            echo "Usage: sql <sql_query>\n";
            return;
        }
        
        $result = $this->conn->query($query);
        
        if ($result === TRUE) {
            echo "✅ SQL executed successfully\n";
        } else if ($result && $result->num_rows > 0) {
            $this->displayResults($result);
        } else {
            echo "❌ Error: " . $this->conn->error . "\n";
        }
    }
    
    private function showPrivileges() {
        $result = $this->conn->query("SHOW GRANTS FOR CURRENT_USER");
        
        if ($result && $result->num_rows > 0) {
            echo "\n🔑 Current User Privileges:\n";
            echo str_repeat("=", 50) . "\n";
            while ($row = $result->fetch_array()) {
                echo "• " . $row[0] . "\n";
            }
        }
    }
    
    private function showProcessList() {
        $result = $this->conn->query("SHOW PROCESSLIST");
        
        if ($result && $result->num_rows > 0) {
            echo "\n🔄 Active Database Connections:\n";
            echo str_repeat("=", 80) . "\n";
            printf("%-5s | %-15s | %-20s | %-30s\n", "ID", "User", "Database", "Command");
            echo str_repeat("-", 80) . "\n";
            while ($row = $result->fetch_assoc()) {
                printf("%-5s | %-15s | %-20s | %-30s\n", 
                    $row['Id'], $row['User'], $row['db'], $row['Command']);
            }
        }
    }
    
    private function shutdownDatabase() {
        if (!$this->is_admin) {
            echo "❌ Permission denied. Admin access required.\n";
            return;
        }
        
        echo "⚠️  WARNING: This will shutdown the database server! Continue? (y/n): ";
        $confirm = trim(fgets(STDIN));
        
        if (strtolower($confirm) === 'y') {
            $this->conn->query("SHUTDOWN");
            echo "✅ Database shutdown command sent.\n";
        } else {
            echo "❌ Shutdown cancelled.\n";
        }
    }
    
    private function listFiles() {
        $files = [
            "database_backups/",
            "sql_scripts/", 
            "logs/",
            "system.log",
            "config.ini",
            "readme.txt",
            "backup_2024.sql"
        ];
        
        echo "📁 Directory Contents:\n";
        foreach ($files as $file) {
            echo "  " . $file . "\n";
        }
    }
    
    private function displayResults($result) {
        $fields = $result->fetch_fields();
        
        echo "\n📊 SQL Results:\n";
        echo str_repeat("=", 80) . "\n";
        
        foreach ($fields as $field) {
            printf("%-20s | ", $field->name);
        }
        echo "\n" . str_repeat("-", 80) . "\n";
        
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            foreach ($row as $value) {
                printf("%-20s | ", substr($value, 0, 20));
            }
            echo "\n";
        }
        echo "Total rows: {$result->num_rows}\n";
    }
}

try {
    $terminal = new TerminalSystem();
    $terminal->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
