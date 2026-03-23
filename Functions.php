<?php

require_once __DIR__ . '/Database.php';
require __DIR__ . "/vendor/autoload.php";

//use GuzzleHttp\Psr7\UploadedFile;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


define('SECRET_KEY', include_once __DIR__ . '/.ignore/config/jwt.php');

//exit (json_encode(SECRET_KEY));
class Functions
{
    private $userLoad = null;

    public function __construct()
    {

        $this->userLoad = [
            'userId' => "68b658c3d6b1e", # Admin
            //'userId' => "68b656042da3f", # Customer
            'email' => "naychurrahl@gmail.com",
            'role' => 'admin',
            'token' => null,
            'use' => 'auth',
        ];

        //$this->userLoad = $this->verifyJWT();
    }

    // === HELPER ===
    private function check_required_fields(array $required, array $data): bool
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing fields', 'fields' => $missing]);
            return false;
        }

        return true;
    }

    private function cleanExplode(string $string, string $e = " "): array
    {
        $name = trim(strtolower(strtoupper(trim($string))));

        $name = explode($e, $name);

        foreach ($name as $value) {

            if (empty(trim($value))) continue;

            $return[] = trim($value);
        }

        return (array) $return;
    }

    private function custom_hash(mixed $data, int $k = 1000): string
    {
        //exit (":::{$k}:::");
        if ($k === 0) {
            return hash("sha256", json_encode($data));
        }

        return  $this->custom_hash(hash("sha256", json_encode($data)), --$k);
    }

    public function consoleLog(mixed $load): void
    {

        exit(json_encode($load));
    }

    private function generateJWT(array $payload, string $secret = SECRET_KEY): string
    {

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", $secret, true);

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    private function getFullName($uuid)
    {
        $db = Database::getInstance();
        $conn = $db->connect();

        try {
            $stmt = $conn->prepare("
                    SELECT n.rawname 
                    FROM fullname f 
                    JOIN names n ON f.name = n.rawname 
                    WHERE f.user = :uuid 
                    ORDER BY f.position ASC
                ");
            $stmt->execute(['uuid' => $uuid]);
            $names = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!$names) {
                echo json_encode(['error' => 'Name not found']);
                return false;
            }

            return ucwords(implode(' ', $names));
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Name not found']);
            return false;
        }
    }

    private function inStock($userId, $bool = true)
    {

        $db = Database::getInstance();
        $conn = $db->connect();

        $user = $conn->prepare("
                SELECT puid, buid, stock FROM products
                WHERE puid = :user
                LIMIT 1
            ");

        $user->execute([':user' => $userId]);

        $user = $user->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $bool ? boolval($user['stock']) : $user;
        }

        http_response_code(404);
        return 404;
    }

    private function imageResize(string $sourcePath, $userId, float $scale = 0.1): bool | string
    {

        // Get image info
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        [$width, $height, $type] = $info;

        // Calculate new dimensions
        $newWidth = (int)($width * $scale);
        $newHeight = (int)($height * $scale);

        // Create image resource from source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false; // Unsupported type
        }

        // Create a new true color image
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        // Resample
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save image

        ob_start();

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($resized, Null, 85); // Quality 85/100
                break;
            case IMAGETYPE_PNG:
                imagepng($resized, Null, 6); // Compression level 0-9
                break;
            case IMAGETYPE_GIF:
                imagegif($resized);
                break;
            default:
                ob_end_clean();
                return False;
        }

        $data = ob_get_clean();

        // Free memory
        imagedestroy($source);
        imagedestroy($resized);

        if (! $data) return false;

        $iv       = random_bytes(16);      // random per image
        $finalKey = hash('sha256', $userId . $iv . SECRET_KEY, true);

        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $finalKey, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) return false;

        // Prepend IV to the encrypted data for later decryption
        //return file_put_contents($destinationPath, $iv . $encrypted) !== false;

        $payload = $iv . $encrypted;

        //shuffle($modes);

        $ext = "idk";

        //$payload = "\x89PNG\r\n\x1a\n" . $payload;

        $payload = "PK\x03\x04" . $payload;

        //$rlo = "\u{202E}";
        //$filename = $userId . '.' . $rlo . $ext;
        $filename = $userId . '.' . $ext;

        // Token derived from filename
        $token = md5($filename);

        $prefix = substr($token, 0, 3); // 3-char prefix

        // Store file with prefix
        $dir = __DIR__ . "/../uploads/";

        $storedName = $prefix . '_' . $filename;

        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedName;

        //file_put_contents($path, $payload);
        file_put_contents($path, $data);

        return $token;
    }

    private function isUser($userId)
    {

        $db = Database::getInstance();
        $conn = $db->connect();

        $user = $conn->prepare("
                SELECT * FROM users
                WHERE uuid = :user
                LIMIT 1
            ");

        $user->execute([':user' => $userId]);


        if ($user->rowCount() > 0) {
            $user = $user->fetch(PDO::FETCH_ASSOC);

            if ($user['is_active']) {
                return [
                    'userId' => $user['uuid'],
                    'role' => $user['role'],
                    'email' => $user['email'],
                ];
            }
        }

        return FALSE;
    }

    private function payment($email, $amountInNaira, $order_id, $callbackUrl = "")
    {

        $secretKey = include_once __DIR__ . "/../config/paystack.php"; // Replace with your secret key
        $amount = $amountInNaira * 100; // Convert to kobo

        $fields = [
            'email' => $email,
            'amount' => $amount,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'order_id' => $order_id,
            ],
        ];

        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $secretKey",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_POST, 1);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['code' => 400, 'message' => ['status' => false, 'message' => $error]];
        }

        $result = json_decode($response, true);
        return ['code' => 200, 'message' => $result['data']];
    }

    /**
     * Sends an email via Gmail SMTP using parameters from an array.
     * Automatically retries using port 465 (SSL) if 587 fails.
     *
     * @param array $params Associative array of email parameters:
     *   - toEmail
     *   - toName
     *   - subject
     *   - body
     *   - fromName
     *
     * @return bool|string True on success, or error message
     */
    private function sendmail(array $params)
    {
        $keys = include_once __DIR__ . "/.ignore/config/mail.php";

        $smtpAttempts = [
            [587, PHPMailer::ENCRYPTION_STARTTLS],
            [465, PHPMailer::ENCRYPTION_SMTPS]
        ];

        $fromName = $params['fromName'] ?? 'no-reply';

        if (! isset($params['toName'])) $params['toName'] = "";

        //$username = "realsexychef@gmail.com";
        $username = $keys['username'];

        foreach ($smtpAttempts as [$port, $encryption]) {
            $mailer = new PHPMailer(true);
            try {
                $mailer->SMTPDebug = SMTP::DEBUG_OFF; // Change to DEBUG_SERVER for verbose
                $mailer->isSMTP();
                $mailer->Host = 'smtp.gmail.com';
                $mailer->SMTPAuth = true;
                $mailer->Username = $username;
                $mailer->Password = $keys['password'];
                $mailer->SMTPSecure = $encryption;
                $mailer->Port = $port;

                $mailer->setFrom($username, $fromName);
                $mailer->addAddress($params['toEmail'], $params['toName']);

                $mailer->Subject = $params['subject'];
                $mailer->Body    = $params['body'];

                if ($mailer->send()) {
                    return TRUE;
                    //return ['code' => 200, 'message' => 'sent'];
                } else {
                    return FALSE;
                    // This part may not be reached if send() throws instead of returning false
                    //return ['code' => 200, 'message' => "Failed on port $port: " . $mailer->ErrorInfo];
                }
            } catch (Exception $e) {
                // Try next port if available
                if ($port === 465) {
                    return FALSE;
                    //return ['code' => 200, 'message' => "Failed on both ports. Last error: " . $e->getMessage()];
                }
            }
        }
        return FALSE;
        //return ['code' => 200, 'message' => "Failed with all SMTP configurations."];
    }

    public function trending(string $shopId = "")
    {
        /**
         * for kron
         * Take top 3
         * drop table
         * assign points [1 => 3, 2 => 2, 3 => 1]
         * position => point
         */
        $db = Database::getInstance();
        $conn = $db->connect();

        try {

            $sql = "

                    SELECT c.name, c.buid
                    FROM trending a 
                    JOIN products b ON a.puid = b.puid 
                    JOIN businesses c ON b.buid = c.buid
                    GROUP BY c.buid
                    ORDER BY a.points DESC
                ";

            if ($shopId) {

                $sql = "
                        SELECT b.name, b.puid as buid
                        FROM trending a 
                        JOIN products b ON a.puid = b.puid 
                        WHERE b.buid = :buid
                        GROUP BY b.puid
                        ORDER BY a.points DESC
                    ";

                $bind["buid"] = $shopId;
            }

            //exit (json_encode($sql));
            $stmt = $conn->prepare($sql);

            $stmt->execute($bind ?? []);

            $names = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //exit (json_encode($names));
            return ($names);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Name not found', 'message' => $e->getMessage()]);
            return false;
        }
    }

    private function updateTrending(string $productId)
    {
        $db = Database::getInstance();

        $conn = $db->connect();

        $stmt = $conn->prepare("
                INSERT INTO trending
                (puid) VALUES (:puid)
                ON DUPLICATE KEY UPDATE
                points = points + 1;
            ");

        //$this -> consoleLog(["point" => ++$point, "puid" => $productId, "trend" => "prod_68b82678eb376"]);

        $stmt->execute(["puid" => $productId]);

        return True;
    }

    private function verifyJWT(): bool | array
    {

        $db = Database::getInstance();
        $conn = $db->connect();

        $stmt = $conn->prepare("
                -- DELETE FROM jwt
                -- WHERE expires_at < NOW()
            ");

        $stmt->execute();

        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        ###################################################
        # $payload =  $this -> verify_jwt($token, SECRET_KEY);
        ###################################################

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            //http_response_code(401);
            return false;
        }

        list($header64, $payload64, $signatureProvided) = $parts;

        $signature = hash_hmac('sha256', $header64 . "." . $payload64, SECRET_KEY, true);

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if (! hash_equals($base64UrlSignature, $signatureProvided)) {

            //http_response_code(401);

            return false;
        }

        $payload = json_decode(base64_decode($payload64), true);

        //return $payload;
        ###################################################

        try {
            if (! $payload) return false;

            $jwtHash = $this->custom_hash($token . $payload['nonce'] ?? null);

            //$jwtHash = $this -> custom_hash($token);

            $stmt = $conn->prepare("
                    SELECT j.expires_at as exp, j.id
                    -- SELECT * 
                    FROM users u
                    LEFT JOIN jwt j ON j.jwt_id = shash(u.uuid, 'jwt')
                    WHERE u.uuid = :uuid
                    AND j.jwt_hash = shash(:jwt_hash, :nonce)
                    LIMIT 1
                ");

            $stmt->bindParam(':uuid', $payload['userId']);
            $stmt->bindParam(':jwt_hash', $jwtHash);
            $stmt->bindParam(':nonce', $payload['nonce']);

            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $row) return False;

            $stmt = $conn->prepare("
                    UPDATE jwt
                    SET expires_at = :newtime
                    -- WHERE jwt_id = shash(:jwtid, 'jwt')
                    WHERE id = :jwtid
                ");
            //exit(json_encode($stmt));

            $newtime = date('Y-m-d H:i:s', time() + (60 * 20));

            $stmt->execute([':jwtid' => $row['id'], ":newtime" => $newtime]);

            //$this -> userLoad = $payload;

            //$this -> consoleLog($payload);

            return $payload;
        } catch (\Throwable $th) {

            http_response_code(500);
            exit(json_encode("Internal error! -561"));
        }
    }

    public function verifyOtp(string $uuid, string $token): bool
    {
        $db = Database::getInstance();
        $conn = $db->connect();

        $stmt = $conn->prepare("
                DELETE FROM otp
                WHERE expires_at < NOW()
            ");

        $stmt->execute();

        $salt = 'otp_' . $uuid;

        $otpHash = hash('sha256', $token . $salt);

        try {
            $stmt = $conn->prepare("
                    DELETE FROM otp
                    WHERE otp_id = shash(:uuid, 'otp_') AND token = :token AND expires_at > NOW()
                    LIMIT 1
                ");

            $stmt->execute([
                'uuid' => $uuid,
                'token' => $otpHash
            ]);

            return boolval($stmt->rowCount()) ? true : false;
        } catch (PDOException $e) {

            echo json_encode([
                'error' => 'OTP verification failed',
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
    // === HELPER! ===

    // == CART ==
    // == CART ==

    // == CATEGORIES ==
    // == CATEGORIES ==

    // == ORDERS ==
    // == ORDERS ==

    // == PRODUCTS ==
        public function addProduct(array $product): void 
        {
            $this -> consoleLog($product);
        }

        public function deleteProduct(string $productId): void {}

        public function fetchProduct(string $productId = null): void
        {


            $db = Database::getInstance();
            $con = $db->connect();

            try {
                $stmt = $con->prepare("
                    SELECT p.id, p.name, p.price, p.image, p.stock, p.description, GROUP_CONCAT(c.category) as category 
                    FROM products p
                    LEFT JOIN productcategories c ON p.name = c.product
                    GROUP BY p.name
                ");

                $stmt->execute();

                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $categories = ["All" => ""];

                $i = 0;

                foreach ($products as $product) {
                    $cats = explode(',', $product['category']);
                    
                    $products[$i]["id"] = (string)$products[$i]["id"];
                    $products[$i]["price"] = (float)$products[$i]["price"];
                    $products[$i]["category"] = $cats;
                    
                    foreach ($cats as $cat) {
                        $categories[$cat] = "";
                    }

                    $i++;
                }

                $categories = array_keys($categories);
                exit($this->consoleLog(["categories" => $categories, "products" => $products]));
            } catch (PDOException $e) {
                $this->consoleLog(['Error!' => $e]);
            }


            /*$products = [
                [
                    "id" => '1',
                    "name" => 'Wireless Headphones',
                    "price" => 79.99,
                    "image" => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400',
                    "stock" => 15,
                    "category" => ['Jeans', 'Children'],
                    "description" => 'High-quality wireless headphones with noise cancellation and long battery life.'
                ],
                [
                    "id" => '2',
                    "name" => 'Smart Watch',
                    "price" => 199.99,
                    "image" => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
                    "stock" => 8,
                    "category" => ['Electronics', 'Pine apple'],
                    "description" => 'Feature-rich smartwatch with health tracking and notifications.'
                ],
                [
                    "id" => '3',
                    "name" => 'Cotton T-Shirt',
                    "price" => 24.99,
                    "image" => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400',
                    "stock" => 50,
                    "category" => ['Ready made'],
                    "description" => 'Comfortable 100% cotton t-shirt in various colors.'
                ],
                [
                    "id" => '4',
                    "name" => 'Coffee Maker',
                    "price" => 89.99,
                    "image" => 'https://images.unsplash.com/photo-1517668808822-9ebb02f2a0e6?w=400',
                    "stock" => 12,
                    "category" => ['Home'],
                    "description" => 'Programmable coffee maker with thermal carafe.'
                ],
                [
                    "id" => '5',
                    "name" => 'Fiction Novel',
                    "price" => 14.99,
                    "image" => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400',
                    "stock" => 30,
                    "category" => ['Books'],
                    "description" => 'Bestselling fiction novel by acclaimed author.'
                ],
                [
                    "id" => '6',
                    "name" => 'Running Shoes',
                    "price" => 119.99,
                    "image" => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400',
                    "stock" => 20,
                    "category" => ['Ready made'],
                    "description" => 'Lightweight running shoes with superior cushioning.'
                ],
                [
                    "id" => '7',
                    "name" => 'Desk Lamp',
                    "price" => 39.99,
                    "image" => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=400',
                    "stock" => 25,
                    "category" => ['Home'],
                    "description" => 'Modern LED desk lamp with adjustable brightness.'
                ],
                [
                    "id" => '8',
                    "name" => 'Bluetooth Speaker',
                    "price" => 59.99,
                    "image" => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400',
                    "stock" => 5,
                    "category" => ['Electronics'],
                    "description" => 'Portable Bluetooth speaker with 360-degree sound.'
                ]
            ];

            foreach ($products as $product) {
                foreach ($product["category"] as $cat) {
                    $categories[$cat] = "";
                }
            }*/


            $categories = array_keys($categories);
            exit($this->consoleLog(["categories" => $categories, "products" => $products]));
            
        }

        public function updateProduct(array $product): void
        {
            $this->consoleLog($product);
        }

    // == PRODUCTS ==

    // === SYSTEM === ✅
    public function ping() //✔️
    {
        if (
            ! $this->userLoad
        ) $this->consoleLog([
            'status' => 'out',
            'timestamp' => time(),
            'message' => 'pong'
        ]);

        $this->consoleLog([
            'status' => 'in',
            'timestamp' => time(),
            'message' => 'pong'
        ]);
    }
    // === SYSTEM === ✅

    // == USERS ==
    // == USERS ==
    

}
