<?php
require_once '../core/Session.php';
require_once '../core/Database.php';

\App\Core\Session::init();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $message = trim(strtolower($data['message'] ?? ''));

    if (empty($message)) {
        echo json_encode(["success" => false, "message" => "Empty message"]);
        exit;
    }

    $db = new \App\Core\Database();
    $conn = $db->getConnection();

    $response = "I'm your MedIn AI Assistant. I can help you with inventory alerts, checking medicine prices, managing your account, and providing medical information.";

    // 1. Password Reset / Login Help
    if (strpos($message, 'password') !== false || strpos($message, 'forget') !== false || strpos($message, 'reset') !== false) {
        $response = "If you've forgotten your password, you can reset it by logging out and clicking 'Forgot Password' on the login screen. You'll receive a secure OTP via email to verify your identity before creating a new password.";
    } 
    // 2. Website Information
    elseif (strpos($message, 'what kind of') !== false || strpos($message, 'about website') !== false || strpos($message, 'what is this website') !== false) {
        $response = "MedIn is a state-of-the-art E-Commerce Pharmacy platform. It allows customers to browse, cart, and securely purchase medicines, while providing administrators and pharmacists a robust dashboard to track inventory, suppliers, and expiring batches.";
    } 
    // 3. Buying Instructions
    elseif (strpos($message, 'how to buy') !== false || strpos($message, 'purchase') !== false) {
        $response = "To buy a medicine, simply search for it in the top bar, add it to your Cart, and click 'Proceed to Checkout' in your Profile Hub. You can pay securely via Card or choose Cash on Delivery.";
    }
    // 4. Medicine Database Query (Price/Availability)
    elseif (strpos($message, 'price of') !== false || strpos($message, 'cost of') !== false || strpos($message, 'do you have') !== false) {
        // Extract medicine name (naive extraction)
        $words = explode(' ', $message);
        $searchQuery = end($words); // Take the last word as a guess, e.g., "price of aspirin"
        
        // Better extraction:
        if (preg_match('/(?:price of|cost of|do you have) ([a-z0-9\s]+)/i', $message, $matches)) {
            $searchQuery = trim($matches[1]);
        }
        
        $stmt = $conn->prepare("SELECT name, price, quantity FROM medicines WHERE name LIKE ? LIMIT 1");
        $stmt->execute(["%" . $searchQuery . "%"]);
        $med = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($med) {
            $response = "Yes, we have " . ucfirst($med['name']) . ". The price is $" . number_format($med['price'], 2) . ". Currently, we have " . $med['quantity'] . " in stock.";
        } else {
            $response = "I couldn't find exactly what you're looking for. Could you specify the medicine name again? E.g., 'What is the price of Aspirin?'";
        }
    }
    // 5. Medicine Uses (Knowledge Base)
    elseif (strpos($message, 'use') !== false || strpos($message, 'uses') !== false || strpos($message, 'what is') !== false) {
        if (preg_match('/(?:use of|uses of|what is) ([a-z0-9]+)/i', $message, $matches)) {
            $medName = trim($matches[1]);
            
            $stmt = $conn->prepare("SELECT name, category FROM medicines WHERE name LIKE ? LIMIT 1");
            $stmt->execute(["%" . $medName . "%"]);
            $med = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($med) {
                $category = strtolower($med['category']);
                if ($category == 'painkiller') {
                    $response = ucfirst($med['name']) . " is a painkiller. It is commonly used to relieve mild to moderate pain, reduce inflammation, and lower fevers. Please consult a doctor before use.";
                } elseif ($category == 'antibiotic') {
                    $response = ucfirst($med['name']) . " is an antibiotic. It is used to treat bacterial infections. You must complete the full prescribed course, even if you feel better.";
                } elseif ($category == 'supplement') {
                    $response = ucfirst($med['name']) . " is a dietary supplement. It helps provide nutrients that may not be consumed in sufficient quantities from your regular diet.";
                } else {
                    $response = ucfirst($med['name']) . " is categorized as " . $category . ". Please check the product description for specific medical uses.";
                }
            } else {
                // Hardcoded fallback logic
                if ($medName == 'aspirin') {
                    $response = "Aspirin is commonly used to reduce pain, fever, or inflammation. It can also be used as a blood thinner.";
                } elseif ($medName == 'amoxicillin') {
                    $response = "Amoxicillin is a penicillin antibiotic used to treat various bacterial infections like pneumonia or bronchitis.";
                } else {
                    $response = "I don't have detailed medical data on '$medName'. Please consult a healthcare professional for exact usage instructions.";
                }
            }
        }
    }
    // Fallbacks
    elseif (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
        $response = "Hello! I am MedIn AI. How can I assist you with medicines, orders, or your account today?";
    } elseif (strpos($message, 'stock') !== false || strpos($message, 'inventory') !== false) {
        $response = "To manage your stock, navigate to the Inventory page. I can also alert you when items are running low or nearing expiry.";
    } elseif (strpos($message, 'expire') !== false || strpos($message, 'expired') !== false) {
        $response = "You can view expiring medicines in the Inventory section. We also have a 'Remove Expired' feature available for bulk deletion.";
    } elseif (strpos($message, 'sale') !== false || strpos($message, 'order') !== false) {
        $response = "Recent orders can be managed in the Orders tab. Make sure to check pending orders regularly.";
    }

    // Simulate AI thinking delay
    usleep(900000);

    echo json_encode([
        "success" => true, 
        "reply" => $response
    ]);
}
