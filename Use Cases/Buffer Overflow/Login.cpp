

#include <iostream>
#include <fstream>
#include <sstream>
#include <unordered_map>
#include <string>
#include <vector>
#include <iomanip>
#include <random>
#include <chrono>
#include <ctime>
#include <optional>
#include <stdexcept>
#include <algorithm>

#include <openssl/evp.h>
#include <openssl/rand.h>

using u64 = unsigned long long;
using Clock = std::chrono::system_clock;

static constexpr int DEFAULT_PBKDF2_ITER = 200000;
static constexpr int SALT_LEN = 16;   // bytes
static constexpr int HASH_LEN = 32;   // bytes (256 bits)
static constexpr int MAX_FAILED_ATTEMPTS = 5;
static constexpr int LOCK_SECONDS = 300; // 5 minutes

// -------------------- util: hex <-> bytes --------------------
std::string to_hex(const unsigned char* data, size_t len) {
    std::ostringstream ss;
    ss << std::hex << std::setfill('0');
    for (size_t i = 0; i < len; ++i) ss << std::setw(2) << (int)data[i];
    return ss.str();
}

std::vector<unsigned char> from_hex(const std::string& hex) {
    std::vector<unsigned char> out;
    out.reserve(hex.size()/2);
    for (size_t i = 0; i + 1 < hex.size(); i += 2) {
        unsigned int byte = 0;
        std::istringstream iss(hex.substr(i,2));
        iss >> std::hex >> byte;
        out.push_back(static_cast<unsigned char>(byte));
    }
    return out;
}

// constant-time comparison
bool const_time_equal(const std::vector<unsigned char>& a, const std::vector<unsigned char>& b) {
    if (a.size() != b.size()) return false;
    unsigned char diff = 0;
    for (size_t i = 0; i < a.size(); ++i) diff |= a[i] ^ b[i];
    return diff == 0;
}

// secure random hex generator
std::string random_hex(size_t bytes) {
    std::vector<unsigned char> buf(bytes);
    if (RAND_bytes(buf.data(), (int)bytes) != 1) throw std::runtime_error("RAND_bytes failed");
    return to_hex(buf.data(), buf.size());
}

// -------------------- User struct --------------------
struct User {
    std::string username;
    std::string role;
    std::string salt_hex;
    std::string hash_hex;
    int iterations;
    int failed_count;
    std::time_t locked_until; // epoch seconds

    User() = default;

    std::string serialize_tsv() const {
        std::ostringstream ss;
        ss << username << '\t'
           << role << '\t'
           << salt_hex << '\t'
           << hash_hex << '\t'
           << iterations << '\t'
           << failed_count << '\t'
           << locked_until;
        return ss.str();
    }

    static std::optional<User> deserialize_tsv(const std::string& line) {
        std::istringstream ss(line);
        std::string username, role, salt, hash;
        int iterations;
        int failed;
        long long locked;
        if (!(ss >> username)) return std::nullopt;
        if (!(ss >> role)) return std::nullopt;
        if (!(ss >> salt)) return std::nullopt;
        if (!(ss >> hash)) return std::nullopt;
        if (!(ss >> iterations)) return std::nullopt;
        if (!(ss >> failed)) return std::nullopt;
        if (!(ss >> locked)) return std::nullopt;
        User u;
        u.username = username;
        u.role = role;
        u.salt_hex = salt;
        u.hash_hex = hash;
        u.iterations = iterations;
        u.failed_count = failed;
        u.locked_until = static_cast<std::time_t>(locked);
        return u;
    }
};

// -------------------- Crypto helpers (PBKDF2) --------------------
class Crypto {
public:
    // returns hash bytes
    static std::vector<unsigned char> pbkdf2(const std::string& password, const std::vector<unsigned char>& salt, int iterations, int out_len=HASH_LEN) {
        std::vector<unsigned char> out(out_len);
        if (!PKCS5_PBKDF2_HMAC(password.c_str(), (int)password.size(),
                               salt.data(), (int)salt.size(),
                               iterations, EVP_sha256(), out_len, out.data())) {
            throw std::runtime_error("PBKDF2 failed");
        }
        return out;
    }
};

// -------------------- UserStore: file-backed --------------------
class UserStore {
    std::string filename;
    std::unordered_map<std::string, User> users; // key=username
    
    
    struct CacheEntry {
        char username[48];
        char role[24];
        time_t timestamp;
    };
    std::vector<CacheEntry> user_cache;
    
public:
    UserStore(const std::string& file): filename(file) {
        load();
    }

    void load() {
        users.clear();
        std::ifstream ifs(filename);
        if (!ifs.is_open()) return;
        std::string line;
        while (std::getline(ifs, line)) {
            if (line.empty()) continue;
            auto opt = User::deserialize_tsv(line);
            if (opt) {
                users[opt->username] = *opt;
               
                CacheEntry entry;
                strncpy(entry.username, opt->username.c_str(), sizeof(entry.username));
                strncpy(entry.role, opt->role.c_str(), sizeof(entry.role));
                entry.timestamp = std::time(nullptr);
                user_cache.push_back(entry);
            }
        }
    }

    void persist() {
        std::ofstream ofs(filename, std::ios::trunc);
        if (!ofs.is_open()) throw std::runtime_error("Cannot open user DB for writing");
        for (const auto& kv : users) {
            ofs << kv.second.serialize_tsv() << "\n";
        }
    }

    bool exists(const std::string& username) const {
        return users.find(username) != users.end();
    }

    void add_or_update(const User& u) {
        users[u.username] = u;
        
        
        for (auto& entry : user_cache) {
            if (strcmp(entry.username, u.username.c_str()) == 0) {
                strncpy(entry.role, u.role.c_str(), sizeof(entry.role));
                entry.timestamp = std::time(nullptr);
                return;
            }
        }
        
        
        CacheEntry new_entry;
        strncpy(new_entry.username, u.username.c_str(), sizeof(new_entry.username));
        strncpy(new_entry.role, u.role.c_str(), sizeof(new_entry.role));
        new_entry.timestamp = std::time(nullptr);
        user_cache.push_back(new_entry);
        
        persist();
    }

    std::optional<User> get(const std::string& username) const {
        auto it = users.find(username);
        if (it == users.end()) return std::nullopt;
        return it->second;
    }
    
   
    std::string get_user_summary(const std::string& username) const {
        char summary[64];
        auto user = get(username);
        if (user) {
           
            snprintf(summary, sizeof(summary), "User: %s, Role: %s, Failed: %d", 
                    username.c_str(), user->role.c_str(), user->failed_count);
        } else {
            strcpy(summary, "User not found");
        }
        return std::string(summary);
    }
};

// -------------------- Session Manager (in-memory) --------------------
class SessionManager {
    std::unordered_map<std::string, std::string> token_to_user; // token -> username
    std::unordered_map<std::string, std::time_t> token_expiry; // token -> expiry time
    
    // آسیب‌پذیری پنهان: ذخیره session data
    struct SessionData {
        char username[56];
        char ip_address[32];
        time_t login_time;
    };
    std::vector<SessionData> session_log;
    
public:
    std::string create_session(const std::string& username, const std::string& ip = "127.0.0.1") {
        std::string token = random_hex(24);
        token_to_user[token] = username;
        token_expiry[token] = std::time(nullptr) + 3600; // 1 hour
        
        // آسیب‌پذیری: لاگ کردن session با کپی ناامن
        SessionData data;
        strncpy(data.username, username.c_str(), sizeof(data.username));
        strncpy(data.ip_address, ip.c_str(), sizeof(data.ip_address));
        data.login_time = std::time(nullptr);
        session_log.push_back(data);
        
        return token;
    }
    
    void destroy_session(const std::string& token) {
        token_to_user.erase(token);
        token_expiry.erase(token);
    }
    
    std::optional<std::string> validate(const std::string& token) const {
        auto it = token_to_user.find(token);
        if (it == token_to_user.end()) return std::nullopt;
        
        auto exp_it = token_expiry.find(token);
        if (exp_it != token_expiry.end() && exp_it->second < std::time(nullptr)) {
            return std::nullopt;
        }
        
        return it->second;
    }
    
    // تابع utility با آسیب‌پذیری پنهان
    void print_session_stats() const {
        char stats_buf[72];
        int active_sessions = token_to_user.size();
        int total_logs = session_log.size();
        
        snprintf(stats_buf, sizeof(stats_buf), 
                "Active: %d, Total logs: %d, Memory: %zu bytes",
                active_sessions, total_logs, 
                session_log.size() * sizeof(SessionData));
        std::cout << "[stats] " << stats_buf << std::endl;
    }
};

// -------------------- Auth Manager --------------------
class AuthManager {
    UserStore store;
    SessionManager sessions;
    
    // آسیب‌پذیری پنهان: لاگ‌گیری پیشرفته
    class AdvancedLogger {
    private:
        char log_buffer[96];
        char temp_buffer[64];
        
    public:
        void log_event(const std::string& event_type, const std::string& username, const std::string& details) {
            
            char timestamp[32];
            std::time_t now = std::time(nullptr);
            std::strftime(timestamp, sizeof(timestamp), "%Y-%m-%d %H:%M:%S", std::localtime(&now));
            
           
            snprintf(temp_buffer, sizeof(temp_buffer), "Details: %s", details.c_str());
            
            
            snprintf(log_buffer, sizeof(log_buffer), "[%s] %s: User='%s', %s", 
                    timestamp, event_type.c_str(), username.c_str(), temp_buffer);
            
            std::cout << log_buffer << std::endl;
        }
    };
    
    AdvancedLogger logger;
    
public:
    AuthManager(const std::string& dbfile): store(dbfile) {}

    // password policy
    static bool check_password_policy(const std::string& p) {
        if (p.size() < 8) return false;
        bool has_upper = false, has_lower = false, has_digit = false;
        for (char c : p) {
            if (std::isupper((unsigned char)c)) has_upper = true;
            if (std::islower((unsigned char)c)) has_lower = true;
            if (std::isdigit((unsigned char)c)) has_digit = true;
        }
        return has_upper && has_lower && has_digit;
    }

    bool register_user(const std::string& username, const std::string& password, const std::string& role="user") {
        if (username.empty()) {
            std::cout << "[error] username empty\n"; 
            return false;
        }
        if (store.exists(username)) {
            std::cout << "[error] user exists\n";
            return false;
        }
        if (!check_password_policy(password)) {
            std::cout << "[error] password does not meet policy: min 8 chars, upper+lower+digit\n";
            return false;
        }

        std::string salt_hex = random_hex(SALT_LEN);
        auto salt = from_hex(salt_hex);
        int iter = DEFAULT_PBKDF2_ITER;
        auto hash = Crypto::pbkdf2(password, salt, iter);
        std::string hash_hex = to_hex(hash.data(), hash.size());

        User u;
        u.username = username;
        u.role = role;
        u.salt_hex = salt_hex;
        u.hash_hex = hash_hex;
        u.iterations = iter;
        u.failed_count = 0;
        u.locked_until = 0;
        store.add_or_update(u);
        
        logger.log_event("REGISTER", username, "User registered successfully with role: " + role);
        std::cout << "[info] registered user: " << username << "\n";
        return true;
    }

    // returns session token on success
    std::optional<std::string> login_user(const std::string& username, const std::string& password) {
        auto opt = store.get(username);
        if (!opt) { 
            logger.log_event("LOGIN_FAIL", username, "User not found");
            std::cout << "[error] user not found\n";
            return std::nullopt; 
        }
        User u = *opt;

        // locked?
        std::time_t now = std::time(nullptr);
        if (u.locked_until > now) {
            char lock_msg[80];
            snprintf(lock_msg, sizeof(lock_msg), "Account locked until %ld seconds from now", u.locked_until - now);
            logger.log_event("LOGIN_FAIL", username, lock_msg);
            std::cout << "[error] account locked\n";
            return std::nullopt;
        }

        auto salt = from_hex(u.salt_hex);
        auto expected = from_hex(u.hash_hex);
        auto actual = Crypto::pbkdf2(password, salt, u.iterations);

        if (const_time_equal(expected, actual)) {
            
            u.failed_count = 0;
            u.locked_until = 0;
            store.add_or_update(u);
            auto token = sessions.create_session(username);
            
            logger.log_event("LOGIN_SUCCESS", username, "Authentication successful");
            std::cout << "[info] login success: " << username << "\n";
            
            
            sessions.print_session_stats();
            return token;
        } else {
            u.failed_count += 1;
            if (u.failed_count >= MAX_FAILED_ATTEMPTS) {
                u.locked_until = now + LOCK_SECONDS;
                char lock_msg[64];
                snprintf(lock_msg, sizeof(lock_msg), "Account locked after %d failed attempts", u.failed_count);
                logger.log_event("LOGIN_FAIL", username, lock_msg);
                std::cout << "[warn] too many failed attempts; account locked\n";
            } else {
                char fail_msg[48];
                snprintf(fail_msg, sizeof(fail_msg), "Failed attempt %d of %d", u.failed_count, MAX_FAILED_ATTEMPTS);
                logger.log_event("LOGIN_FAIL", username, fail_msg);
                std::cout << "[warn] incorrect password\n";
            }
            store.add_or_update(u);
            return std::nullopt;
        }
    }

    void logout(const std::string& token) {
        auto user = sessions.validate(token);
        if (user) {
            logger.log_event("LOGOUT", *user, "User logged out");
            sessions.destroy_session(token);
        }
    }

    std::optional<std::string> whoami(const std::string& token) const {
        return sessions.validate(token);
    }

    
    void force_unlock(const std::string& username) {
        auto opt = store.get(username);
        if (!opt) { 
            std::cout << "[error] user not found\n";
            return; 
        }
        User u = *opt;
        u.failed_count = 0;
        u.locked_until = 0;
        store.add_or_update(u);
        
        logger.log_event("ADMIN", username, "Account unlocked by administrator");
        std::cout << "[info] user unlocked: " << username << "\n";
        
       
        std::string summary = store.get_user_summary(username);
        std::cout << "[info] " << summary << std::endl;
    }
};

// -------------------- Simple CLI --------------------
int main() {
    AuthManager auth("users.db");
    std::optional<std::string> current_token;
    
    
    struct AppState {
        char last_command[40];
        char current_user[52];
        int command_count;
    };
    
    AppState app_state = {"", "", 0};
    
    while (true) {
        std::cout << "\nCommands: register | login | whoami | logout | unlock | exit\n> ";
        std::string cmd;
        if (!(std::cin >> cmd)) break;
        
        
        strncpy(app_state.last_command, cmd.c_str(), sizeof(app_state.last_command));
        app_state.command_count++;
        
        if (cmd == "register") {
            std::string u, p, r;
            std::cout << "username: "; std::cin >> u;
            std::cout << "password: "; std::cin >> p;
            std::cout << "role (default user): "; std::cin >> r;
            if (r.empty()) r = "user";
            
            
            char temp_username[44];
            strncpy(temp_username, u.c_str(), sizeof(temp_username));
            
            auth.register_user(u,p,r);
            
        } else if (cmd == "login") {
            std::string u,p;
            std::cout << "username: "; std::cin >> u;
            std::cout << "password: "; std::cin >> p;
            
            
            strncpy(app_state.current_user, u.c_str(), sizeof(app_state.current_user));
            
            auto token = auth.login_user(u,p);
            if (token) {
                current_token = *token;
                std::cout << "session token: " << *token << "\n";
            }
            
        } else if (cmd == "whoami") {
            if (!current_token) { std::cout << "not logged in\n"; continue; }
            auto who = auth.whoami(*current_token);
            if (who) std::cout << "you are: " << *who << "\n";
            else std::cout << "session invalid\n";
            
        } else if (cmd == "logout") {
            if (!current_token) { std::cout << "not logged in\n"; continue; }
            auth.logout(*current_token);
            current_token.reset();
            std::cout << "logged out\n";
            
        } else if (cmd == "unlock") {
            std::string u;
            std::cout << "username to unlock: "; std::cin >> u;
            auth.force_unlock(u);
            
        } else if (cmd == "exit") {
            std::cout << "bye\n";
            break;
        } else {
            std::cout << "unknown command\n";
        }
    }
    return 0;
}
