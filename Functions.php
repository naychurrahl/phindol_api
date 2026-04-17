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

    // == BLOG ==
    public function addBlog(array $blog): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $blog]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function deleteBlog(string $blogId): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $$blogId]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function fetchBlog(string $blogId = null): void
    {
        $blogCategories = [];

        $blogPosts = [
            [
                "id" => 1,
                "title" => 'Understanding Life Insurance: A Comprehensive Guide for Families',
                "excerpt" => 'Learn everything you need to know about choosing the right life insurance policy for your family\'s future security and peace of mind.',
                "author" => 'Dr. Adewale Johnson',
                "date" => 'February 15, 2026',
                "readTime" => '5 min read',
                "category" => 'Life Insurance',
                "image" => '"https" =>//images.unsplash.com/photo-1769674109078-da12f5cc7871?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsaWZlJTIwaW5zdXJhbmNlJTIwaGFwcHklMjBmYW1pbHl8ZW58MXx8fHwxNzcyMzA1MzkzfDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
                "content" => "
                                <p>Life insurance is one of the most important financial decisions you'll make for your family. It provides financial security and peace of mind, ensuring that your loved ones are protected even when you're no longer around to provide for them.</p>

                                <h2>What is Life Insurance?</h2>
                                <p>Life insurance is a contract between you and an insurance company. In exchange for premium payments, the insurance company provides a lump-sum payment, known as a death benefit, to your beneficiaries after your death.</p>

                                <h2>Types of Life Insurance</h2>
                                <p>There are two main types of life insurance:</p>
                                
                                <h3>1. Term Life Insurance</h3>
                                <p>Term life insurance provides coverage for a specific period, typically 10, 20, or 30 years. It's the most affordable option and is ideal for temporary needs like mortgage protection or income replacement while your children are young.</p>

                                <h3>2. Whole Life Insurance</h3>
                                <p>Whole life insurance provides lifelong coverage and includes a savings component called cash value. While premiums are higher, this policy builds value over time that you can borrow against or withdraw.</p>

                                <h2>How Much Coverage Do You Need?</h2>
                                <p>A general rule of thumb is to have coverage worth 10-12 times your annual income. However, your specific needs depend on factors like:</p>
                                <ul>
                                    <li>Outstanding debts (mortgage, loans)</li>
                                    <li>Number of dependents</li>
                                    <li>Future education costs</li>
                                    <li>Final expenses</li>
                                    <li>Existing savings and investments</li>
                                </ul>

                                <h2>Why Choose Phindol Insurance?</h2>
                                <p>At Phindol Insurance, we understand that every family is unique. Our experienced advisors work with you to assess your needs and find the perfect policy that fits your budget and provides comprehensive protection for your loved ones.</p>

                                <p>Ready to secure your family's future? Contact us today for a free consultation and personalized quote.</p>
                                ",
            ],

            [
                "id" => 2,
                "title" => 'Understand Life : A Comprehensive Guide for Families',
                "excerpt" => 'Learn everything you need to know about choosing the right life insurance policy for your family\'s future security and peace of mind.',
                "author" => 'Dr. Adewale Johnson',
                "date" => 'February 15, 2026',
                "readTime" => '5 min read',
                "category" => 'Life Insurance',
                "image" => '"https" =>//images.unsplash.com/photo-1769674109078-da12f5cc7871?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsaWZlJTIwaW5zdXJhbmNlJTIwaGFwcHklMjBmYW1pbHl8ZW58MXx8fHwxNzcyMzA1MzkzfDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
                "content" => "
                                <p>Life insurance is one of the most important financial decisions you'll make for your family. It provides financial security and peace of mind, ensuring that your loved ones are protected even when you're no longer around to provide for them.</p>

                                <h2>What is Life Insurance?</h2>
                                <p>Life insurance is a contract between you and an insurance company. In exchange for premium payments, the insurance company provides a lump-sum payment, known as a death benefit, to your beneficiaries after your death.</p>

                                <h2>Types of Life Insurance</h2>
                                <p>There are two main types of life insurance:</p>
                                
                                <h3>1. Term Life Insurance</h3>
                                <p>Term life insurance provides coverage for a specific period, typically 10, 20, or 30 years. It's the most affordable option and is ideal for temporary needs like mortgage protection or income replacement while your children are young.</p>

                                <h3>2. Whole Life Insurance</h3>
                                <p>Whole life insurance provides lifelong coverage and includes a savings component called cash value. While premiums are higher, this policy builds value over time that you can borrow against or withdraw.</p>

                                <h2>How Much Coverage Do You Need?</h2>
                                <p>A general rule of thumb is to have coverage worth 10-12 times your annual income. However, your specific needs depend on factors like:</p>
                                <ul>
                                    <li>Outstanding debts (mortgage, loans)</li>
                                    <li>Number of dependents</li>
                                    <li>Future education costs</li>
                                    <li>Final expenses</li>
                                    <li>Existing savings and investments</li>
                                </ul>

                                <h2>Why Choose Phindol Insurance?</h2>
                                <p>At Phindol Insurance, we understand that every family is unique. Our experienced advisors work with you to assess your needs and find the perfect policy that fits your budget and provides comprehensive protection for your loved ones.</p>

                                <p>Ready to secure your family's future? Contact us today for a free consultation and personalized quote.</p>
                                ",
            ],
        ];

        if ($blogId) {
            $this->consoleLog([
                "blogPosts" => $blogPosts[$blogId] ?? null,
                "blogCategories" => $blogPosts[$blogId]['category'] ?? null,
            ]);
        }
        foreach ($blogPosts as $value) {

            $blogCategories[] = $value["category"];
        }
        $this->consoleLog([
            "blogPosts" => $blogPosts,
            "blogCategories" => array_unique($blogCategories)
        ]);
    }

    public function updateBlog(array $blog): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(["status" => '200', "id" => $blog]);
                break;

            default:
                http_response_code(402);
                $this->consoleLog(["status" => '402']);
                break;
        }
    }
    // == BLOG ==

    // == GALLERY ==
    public function addGallery(array $gallery): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $gallery]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function deleteGallery(string $galleryId): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $galleryId]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function fetchGallery(string $galleryId = null): void
    {
        $galleryImages = [
            [
                "id" => 1,
                "url" => "https://images.unsplash.com/photo-1740818576358-7596eb883cf3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbnN1cmFuY2UlMjBtZWV0aW5nJTIwY29uc3VsdGF0aW9ufGVufDF8fHx8MTc3MjMwNzAwM3ww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
                "title" => "Client Consultation",
                "category" => "clients",
            ],
            [
                "id" => 2,
                "url" => "https://images.unsplash.com/photo-1740818576358-7596eb883cf3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbnN1cmFuY2UlMjBtZWV0aW5nJTIwY29uc3VsdGF0aW9ufGVufDF8fHx8MTc3MjMwNzAwM3ww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
                "title" => "Client Consultation",
                "category" => "not clients",
            ],
        ];

        $galleryCategories = [];

        if ($galleryId) {
            $this->consoleLog([
                "galleryImages" => $galleryImages[$galleryId] ?? null,
                "galleryCategories" => $galleryImages[$galleryId]['category'] ?? null,
            ]);
        }

        foreach ($galleryImages as $value) {

            $galleryCategories[] = $value["category"];
        }

        $this->consoleLog([
            "galleryImages" => $galleryImages,
            "galleryCategories" => array_unique($galleryCategories)
        ]);
    }

    public function updateGallery(array $gallery): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $gallery]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }
    // == GALLERY ==

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

    // == TESTIMONIALS ==
    public function addTestimonials(array $testimonials): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $testimonials]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function deleteTestimonials(string $testimonialsId): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $testimonialsId]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function fetchTestimonials(string $testimonialsId = null): void
    {
        $testimonials = [
            [
                "id" => 1,
                "name" => "Oluwaseun Adeyemi",
                "company" => "TechCorp Nigeria",
                "text" => "Phindol Insurance made the process of securing our corporate insurance seamless. Their team is professional, responsive, and genuinely cares about our needs.",
                "rating" => 5,
            ],
            [
                "id" => 2,
                "name" => "Amaka Nwankwo",
                "company" => "Private Client",
                "text" => "After losing my husband, the life insurance payout helped me secure my children's future. Phindol handled everything with compassion and efficiency.",
                "rating" => 5,
            ],
            [
                "id" => 3,
                "name" => "Chukwudi Okonkwo",
                "company" => "Okonkwo Enterprises",
                "text" => "Their claims support is outstanding. When we had a fire incident, they processed our claim quickly and helped us get back on our feet.",
                "rating" => 5,
            ],
        ];

        if ($testimonialsId) {
            $this->consoleLog($testimonials[$testimonialsId] ?? null);
        }

        $this->consoleLog($testimonials);
    }

    public function updateTestimonials(array $testimonials): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $testimonials]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }
    // == TESTIMONIALS ==

    // == USERS ==
    public function addUsers(array $user): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $user]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function deleteUsers(string $userId): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $userId]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }

    public function fetchUsers(string $userId = null): void
    {
        $users = [
            [
                "id" => 1,
                "name" => "Sarah Johnson",
                "email" => "sarah.johnson@techcorp.com",
                "image_url" => "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400",
                "role_id" => 1,
                "department" => "Executive"
            ],
        ];

        if ($userId) {
            $this->consoleLog($users[$userId] ?? null);
        }

        $this->consoleLog($users);
    }

    public function updateUsers(array $user): void
    {
        switch ($k = rand()) {
            case $k % 5 == 1:
                $this->consoleLog(
                    ["status" => '200', "id" => $user]
                );
                break;

            default:
                http_response_code(402);
                $this->consoleLog(
                    ["status" => '402']
                );
                break;
        }
    }
    // == USERS ==




}
