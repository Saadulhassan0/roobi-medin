<?php
namespace App\Middleware;

require_once __DIR__ . '/../core/Session.php';
use App\Core\Session;

class RoleMiddleware {
    public static function handle($allowedRoles = []) {
        Session::init();
        
        $userRole = Session::get('user_role');

        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            if (self::isApiRequest()) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Forbidden. Insufficient permissions."]);
                exit;
            } else {
                // Redirect to a generic unauthorized page or back to their specific dashboard
                self::redirectBasedOnRole($userRole);
                exit;
            }
        }
    }

    private static function redirectBasedOnRole($role) {
        switch ($role) {
            case 'admin':
                header('Location: /app/views/admin/dashboard.php');
                break;
            case 'pharmacist':
                header('Location: /app/views/pharmacist/dashboard.php');
                break;
            case 'supplier':
                header('Location: /app/views/supplier/dashboard.php');
                break;
            case 'customer':
                header('Location: /app/views/customer/dashboard.php');
                break;
            default:
                header('Location: /public/index.html');
                break;
        }
    }

    private static function isApiRequest() {
        return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
               (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    }
}
?>
