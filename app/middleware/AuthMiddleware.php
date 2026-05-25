<?php
namespace App\Middleware;

require_once __DIR__ . '/../core/Session.php';
use App\Core\Session;

class AuthMiddleware {
    public static function handle() {
        Session::init();
        
        if (!Session::get('user_id')) {
            // Store intended URL for redirecting back after login (optional)
            // Session::set('intended_url', $_SERVER['REQUEST_URI']);
            
            if (self::isApiRequest()) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized access."]);
                exit;
            } else {
                header('Location: /public/index.html');
                exit;
            }
        }
    }

    private static function isApiRequest() {
        return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
               (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    }
}
?>
