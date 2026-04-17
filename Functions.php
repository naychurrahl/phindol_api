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

    // == BOARD MEMBERS ==
    public function fetchBoard(): void
    {
        $board = [
            [
                "id" => 1,
                "name" => "Amb.  Udoyen Victor Etim",
                "position" => "Chairman Board of Directors",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [],
            ],
            [
                "id" => 2,
                "name" => "Mr. Obadiah Othman Aloko, fsi",
                "position" => "Non Executive Director",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Mr.  Obadiah Othman Aloko brings over 35 years of distinguished experience in Nigeria’s Foreign Service to our board. Born in 1962 in Kpangwa, Jenkwe, Obi LGA, Nasarawa State, he holds a B.Sc. in Political Science from Ahmadu Bello University, Zaria (1983), and an M.Sc. in International Relations from the University of Abuja (2007).
                            His illustrious diplomatic career includes pivotal roles in China, Cameroon, and Ghana, where he served as Deputy High Commissioner. Rising to the rank of Director in Nigeria’s Ministry of Foreign Affairs, Ambassador Aloko played a key role in shaping the nation’s international policies. His expertise in diplomacy, management, security, and trade negotiation has earned him multiple awards and recognition for his contributions to national and global engagements.
                            A passionate advocate for national development, Ambassador Aloko is deeply committed to youth empowerment in Nigeria. He is married with five children and brings a wealth of strategic insight and global perspective to our board.",
                ],
            ],
            [
                "id" => 3,
                "name" => "Mr. Hamzat Ibrahim",
                "position" => "Non Executive Director",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Mr. Hamzat Ibrahim, born in 1960 in Bakori, Katsina State, brings over three decades of distinguished service in Nigeria’s Foreign Service to our board. He holds a B.Sc. in Business Administration from Ahmadu Bello University, Zaria (1985), complemented by professional training at the Foreign Service Academy and courses in computer appreciation, citizenship, and leadership in Lagos.
                        His illustrious diplomatic career spans impactful postings, including Namibia (1990), where he contributed to the Convention for a Democratic South Africa (CODESA), aiding the end of apartheid. He served in Morocco (2003–2007), coordinating the rescue and repatriation of over 3,500 undocumented Nigerians, and in Cameroon (2012–2015), where he played a key role in concluding the Greentree Agreement on Bakassi. As Ambassador Extraordinary and Plenipotentiary to Iran (2017–2020), with concurrent accreditation to Armenia, Azerbaijan, and Kazakhstan, he led Nigeria’s diplomatic efforts with distinction.
                        Mr. Ibrahim represented Nigeria in numerous high-level international engagements, including Bi-National Commissions with South Africa, China, and others, as well as summits like the African Union Summit in Kampala (2010) and the D8 Summit in Abuja (2010). As Director of the West Africa Division (2015–2016), he shaped Nigeria’s regional foreign policy. His contributions to global diplomacy, migration, and human rights have earned him widespread respect.
                        Retired in 2020, Ambassador Ibrahim brings strategic insight and a global perspective to our board, enhancing our mission for growth and impact.",
                ],
            ],
            [
                "id" => 4,
                "name" => "Mrs. Ifeyinwa Angela Nworgu",
                "position" => "Non Executive Director",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Mrs. Ifeyinwa Angela Nworgu is a distinguished legal practitioner and expert in multilateral diplomacy, anti-money laundering (AML/CFT), anti-human trafficking, and international peace and security. With a career spanning over three decades, she brings unparalleled expertise to our board. Called to the Nigerian Bar in 1984, she practiced law before joining Nigeria’s Foreign Service, where she represented the nation with distinction across multiple global roles.
                        Her illustrious career includes serving as Nigeria’s Representative to the Sixth Committee of the United Nations (2006–2010), Senior Special Adviser on Legal Matters to the 64th President of the UN General Assembly (2010–2011), and Senior Special Adviser on Global Peace and Security to the 74th President of the UN General Assembly (2019–2020), where she was the Focal Point for Africa. As Director of the Nigerian Special Control Unit Against Money Laundering (2011–2013), she pioneered compliance frameworks for anti-money laundering and counter-terrorism financing. She also served as Consul in Rome, Italy (2015–2018), addressing human trafficking and migration challenges, and as Director of Legal at the Nigerian Ministry of Foreign Affairs (2013–2015, 2018–2019).
                        Since 2020, Mrs. Nworgu has been Chairman of the Board of Trustees at the Center for Fiscal Transparency and Integrity Watch (CeFTIW), a UNCAC Coalition Board Member representing Sub-Saharan Africa. She holds certificates in Corruption Studies from the University of Hong Kong and the Law of the Sea from the Rhodes Academy, and is a UNODC-certified Corruption Risk Assessor, a certified Management Consultant, and a Fellow of the Institute of Management Consultants.
                        Mrs. Nworgu’s global perspective and commitment to transparency and security enhance our board’s strategic vision.",
                ],
            ],
            [
                "id" => 5,
                "name" => "Mr. Babatunde Tajudeen Shonubi",
                "position" => "Non Executive Director",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Ambassador Babatunde Tajudeen Shonubi brings over 35 years of exemplary service as a technocrat in Nigeria’s Foreign Service to our board. A graduate of the University of Ilorin with a Bachelor’s Degree in Education, he rose to the rank of Director on Special Grade Level 17 in the Ministry of Foreign Affairs, Abuja, demonstrating exceptional leadership and dedication.
                        His diplomatic career was enriched by specialized training at the Foreign Service Academy, the Citizenship and Leadership Training Centre (Sea School), and the Management Techniques Workshop at the Centre for Management Development. Renowned for his expertise in diplomacy, bureaucracy, analysis, communication, and time management, Ambassador Shonubi significantly advanced Nigeria’s interests through key overseas postings in Saudi Arabia, Cameroon, the United Arab Emirates, and other nations. His efforts bolstered Nigeria’s domestic and international policy frameworks.
                        Happily married with children, Ambassador Shonubi’s strategic vision and global experience strengthen our board’s commitment to impactful leadership and growth.",
                ],
            ],
            [
                "id" => 6,
                "name" => "Omodele Stephen Adesogan",
                "position" => "MD/CEO",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Omodele Stephen Adesogan is a highly accomplished insurance professional with nearly 30 years of experience in insurance brokerage, underwriting, and strategic leadership. A results-driven executive, he has held key managerial and executive positions across leading insurance firms in Nigeria, demonstrating exceptional expertise in business development, marketing, and corporate leadership.",
                ],
            ],
        ];

        $this->consoleLog($board);
    }
    // == BOARD MEMBERS ==

    // == CART ==
    // == CART ==

    // == CATEGORIES ==
    // == CATEGORIES ==

    // == MANAGEMENT ==
    public function fetchManagement(): void
    {
        $management = [
            [
                "id" => 1,
                "name" => "Omodele S. Adesogan – MBA, ACII, ANIMN",
                "position" => "Pioneer Managing Director/Chief Executive Officer, Phindol Insurance Brokers Limited",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Stephen Omodele Adesogan is a highly accomplished insurance professional with nearly 30 years of experience in insurance brokerage, underwriting, and strategic leadership. A results-driven executive, he has held key managerial and executive positions across leading insurance firms in Nigeria, demonstrating exceptional expertise in business development, marketing, and corporate leadership.",
                    "Career Highlights" => "* 1994 – Began his career at Clarkson Edu & Partners, a leading insurance brokerage firm.
                                    * 1998 – Joined Whispering Hope Insurance Company (now Sterling Assurance) as Branch Manager; promoted to Head of Marketing in 2000.
                                    2003 – Joined Equity Indemnity Insurance (Now Sunu Insurance),and led Marketing and Underwriting Units at Equity Indemnity Insurance.
                                    * 2005 – Appointed Head of Marketing at Fire Equity & General Insurance Company Limited (now Custodian Insurance Company Limited).
                                    * 2007 – Became Managing Director/CEO of Fidelity Bond of Nigeria Limited (Insurance and Reinsurance Brokers).
                                    * 2010 – Served as Regional Manager, Abuja/North, at WAPIC Life.
                                    * 2013 – Appointed Managing Director/CEO of MIB Insurance Brokers Limited.
                                    * 2017 – Led Oracle Insurance Brokers.
                                    2023: Joined Fsl Ins Brokers and became the Acting Managing Director/CEO in 2024
                                    * 2025 – Assumed the role of Pioneer Managing Director/CEO of Phindol Insurance Brokers Limited.
                                    ",
                    "Professional Development & Expertise" => "Stephen has undergone extensive training in Marketing, Underwriting, Claims, Management, and Leadership. His visionary leadership and deep industry knowledge have been instrumental in driving business growth, fostering strategic partnerships, and enhancing operational excellence in the insurance sector",
                ],
            ],
            [
                "id" => 2,
                "name" => "Shehu Abdulrahman – MBA, MNIM , FNHR",
                "position" => "Director, Corporate Services",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Shehu Abdulrahman is a seasoned corporate executive with extensive expertise in Governance, Administration, Financial Management, Human Resources, and Strategic Business Operations. As the Director of Corporate Services at Phindol Insurance Brokers Limited, he plays a pivotal role in ensuring seamless administrative functions, financial oversight, and regulatory compliance while supporting business growth and operational efficiency.",
                    "Career Highlights" => "1990 – Began his career as a National Youth Service Corps (NYSC) member at Continental Merchant Bank Plc, Kano.
                                        1992 – Served as an Audit Officer at the Office of the Auditor-General for the Federation, Abuja, gaining foundational experience in financial oversight and compliance.
                                        1993 – Joined Guaranty Trust Bank Plc, and ranked up to the position of an Assistant Manager, where he played a key role in banking operations and financial management.
                                        1997 – Transitioned to the telecommunications sector as Sales & Channel Distribution Manager at Intercellular Nigeria Limited, overseeing Lagos and Abuja markets.
                                        2002 – Led corporate collections as Team Lead at MTN Nigeria Communications Plc, Lagos, streamlining revenue management.
                                        2004 – Appointed Senior Manager (Head, Corporate Sales) at Nigerian Telecommunications Limited Corporate HeadQuarters, Abuja, driving corporate sales strategy.
                                        2008 – Became Business Manager at Galaxy Backbone Limited, Abuja, managing corporate and government sector engagements especially all state governments.
                                        2011–2019: Served as Senior Special Assistant to the Kwara State Governor on Solid Minerals, Offering Consultancy Services on industry policies and development strategies for sustainable resource management, investment promotion, and economic diversification in the state.
                                        2016 – Took on an academic role as an Adjunct Lecturer on Business and Entrepreneurship at Kwara State University, sharing industry expertise with students.
                                        2019 – Transitioned into business and solid minerals consulting, leveraging extensive experience in governance and corporate leadership.
                                        2025 – Appointed Director, Corporate Services at Phindol Insurance Brokers Limited, overseeing corporate strategy, operations, and stakeholder relations.
                                    ",
                    "Professional Development & Expertise" => "Shehu Abdulrahman is a seasoned corporate executive with deep expertise in governance, administration, marketing, sales, and stakeholder engagement. With a diverse career spanning banking, telecommunications, government, and consulting, he brings a wealth of knowledge and leadership to Phindol Insurance Brokers Limited. His strategic approach to corporate management, financial oversight, and operational excellence continues to drive sustainable growth and business efficiency.",
                ],
            ],
            [
                "id" => 3,
                "name" => "Opeyemi Abimbola – ACIRLM, CIIN",
                "position" => "Head Marketing - Phindol Insurance Brokers Limited",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Opeyemi Abimbola is a dynamic marketing professional with expertise in strategic planning, client engagement, and business development within the public sector and parastatal organizations. As the Head of Marketing (Corporate & Parastatals) at Phindol Insurance Brokers Limited, she plays a pivotal role in driving market expansion and strengthening key institutional relationships. See more",
                    "Career Highlights" => "2010 – Began her career as an Administrative Assistant at Industrial and General Insurance PLC.
                                        2011 – Appointed Executive Assistant (Marketing) at Industrial and General Insurance PLC, supporting business development initiatives.
                                        2012 – Transitioned to the role of Executive Assistant (Branch Accountant), gaining expertise in financial operations.
                                        2014 – Promoted to Executive II (Branch Underwriter/Business Development) at Industrial and General Insurance PLC, where she played a key role in underwriting and expanding client relationships.
                                        2023 – Became Branch Manager at Oceanline Insurance Brokers Ltd, leading operations and business growth strategies.
                                        2025 – Appointed Head of Marketing (Public Sector & Parastatals) at Phindol Insurance Brokers Limited, overseeing strategic marketing initiatives and partnerships.",
                    "Professional Development & Expertise" => "Opeyemi Abimbola brings a wealth of experience in insurance marketing, underwriting, and business development. With a background in financial operations, client engagement, and strategic planning, she has successfully managed teams, expanded market reach, and fostered long-term partnerships. Her commitment to excellence and results-driven approach continues to strengthen Phindol Insurance Brokers’ presence in the public sector.",
                ],
            ],
            [
                "id" => 4,
                "name" => "Adewole Michael Tunde",
                "position" => "Head of Underwriting & Claims - Phindol Insurance Brokers Limited",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Adewole Michael Tunde is an accomplished insurance professional with over 12 years of experience spanning the shipping, logistics, and insurance sectors. As the Head of Underwriting & Claims at Phindol Insurance Brokers Limited, he plays a critical role in risk assessment, claims management, and ensuring seamless policy execution for clients.
                                        A specialist in business development, bid and tender participation, and claims processing, Adewole is well-versed in industry best practices. His strong stakeholder management capabilities and deep understanding of underwriting principles enable him to drive operational excellence and customer satisfaction. See More",
                    "Career Highlights" => "2013 – Began his career as an Insurance and Administrative Officer at Murphy Shipping and Commercial Services Ltd/MIB Insurance Brokers Ltd and Liberty & Trust Insurance Brokers Ltd, gaining foundational experience in underwriting and claims processing.
                                        2018 – Promoted to Manager, Technical & Team Lead at Liberty & Trust Insurance Brokers Ltd, overseeing underwriting operations and technical support.
                                        2025 – Appointed Head of Underwriting & Claims at Phindol Insurance Brokers Limited, leading risk assessment, claims management, and policy execution.",
                    "Professional Development & Expertise" => "Adewole Michael T is a highly skilled insurance professional with extensive experience in underwriting, claims management, and business development. His expertise in stakeholder engagement, bid and tender participation, and operational efficiency has made him a key figure in the industry. With a strong work ethic and a detail-oriented approach, he continues to drive excellence in underwriting and claims processing at Phindol Insurance Brokers Limited.",
                ],
            ],
            [
                "id" => 5,
                "name" => "Hellen Ene Odukoya",
                "position" => "Head of Account - Phindol Insurance Brokers Limited",
                "image" =>
                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                "meta" => [
                    "bio" => "Hellen Ene Odukoya is a results-driven accounting professional with a strong background in financial management, reporting, and corporate accounting. As the Head of Account at Phindol Insurance Brokers Limited, she oversees financial operations, ensuring accuracy, compliance, and efficiency in financial planning and execution",
                    "Career Highlights" => "2008 – Began her career as an Assistant Head Teacher at Dove Children School, Karmo, Abuja, developing leadership and organizational skills.
                                        2012 – Served as Sisters’ Welfare Coordinator for the Female Campus Students of DLBC, demonstrating her leadership and mentorship abilities.
                                        2017 – Completed her National Youth Service Corps (NYSC) as an Account Clerk in the Finance & Accounting Department at the Nigerian Institute of Leather Research and Science Technology, Zaria, Kaduna State.
                                        2017 – Acted as a Motivator for the PETS & HCT CDS group, focusing on sensitization efforts at the LACA Office, Ikere, Ekiti State.
                                        2025 – Appointed Head of Account at Phindol Insurance Brokers Limited, overseeing financial management, reporting, and compliance.",
                    "Professional Development & Expertise" => "Helen Ene Odukoya is a skilled financial expert with a background in accounting, financial analysis, and strategic planning. With a B.Sc in Accounting from Ahmadu Bello University, Zaria, she has developed expertise in financial management, Microsoft Office applications, negotiation, and conflict resolution. Her leadership, communication, and time management skills make her a key figure in ensuring financial efficiency and regulatory compliance at Phindol Insurance Brokers Limited.",
                ],
            ],
        ];

        $this->consoleLog($management);
    }
    // == MANAGEMENT ==

    // == ORDERS ==
    // == ORDERS ==

    // == PARTNERS ==
    public function fetchPartners(): void
    {
        $management = [];

        $this->consoleLog($management);
    }
    // == PARTNERS ==

    // == PRODUCTS ==
    public function addProduct(array $product): void
    {
        $this->consoleLog($product);
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

    // == SERVICES ==
    public function fetchServices(): void
    {
        $services = [
            [
                "id" => 1,
                "title" => "Risk Assessment/Management",
                "slug" => "Risk-Assessment-Management",
                "description" => "We are committed to helping you safeguard your business and assets with expert risk assessment and management solutions. Our team analyzes potential risks, identifies vulnerabilities, and provides tailored strategies to minimize financial and operational threats. Whether it’s regulatory compliance, business continuity, or loss prevention, we offer proactive solutions to keep you protected.",
                "icon" => null,
                "cta" => "Get in touch today to learn more about how we can help.",
            ],
            [
                "id" => 2,
                "title" => "Surveying",
                "slug" => "surveying",
                "description" => "Accurate insurance coverage starts with a thorough assessment.Our expert surveying services help identify risks, determine asset values, and ensure you have the right protection in place. Whether for property, business, or specialized assets, we provide detailed evaluations to support informed insurance decisions.",
                "icon" => "Building2",
                "cta" => "Contact us today to schedule a survey!",
            ],
            [
                "id" => 3,
                "title" => "Client Services",
                "slug" => "client-services",
                "description" => "We prioritize your peace of mind with personalized client services tailored to your unique insurance needs. From policy guidance and claims assistance to risk management support, our team is here to provide expert advice and seamless service. Whether you’re an individual or a business, we ensure you get the protection and support you deserve.",
                "icon" => "FileText",
                "cta" => "Reach out to us today for exceptional client care.",
            ],
            [
                "id" => 4,
                "title" => "Claims Management",
                "slug" => "claims-management",
                "description" => "Filing an insurance claim can be complex, but we make the process smooth and hassle-free. Our dedicated claim management team assists you every step of the way—from documentation and submission to follow-ups and settlements—ensuring you get the compensation you deserve as quickly as possible. Let us handle your claims with expertise and efficiency.",
                "icon" => "FileText",
                "cta" => "Contact us today!",
            ],
            [
                "id" => 5,
                "title" => "Competitive Pricing",
                "slug" => "competitive-pricing",
                "description" => "We believe quality insurance should be both reliable and affordable. We partner top Insurance Companies to provide you with the best coverage at the most competitive rates. Whether for personal or business needs, we tailor cost-effective solutions without compromising protection.",
                "icon" => "FileText",
                "cta" => "Get a quote today and discover the best value for your insurance needs",
            ],
            [
                "id" => 6,
                "title" => "Consultancy",
                "slug" => "consultancy",
                "description" => "Making the right insurance decisions requires expert guidance. Our consultancy services provide personalized advice to help you choose the best coverage, manage risks, and optimize your policies. From business insurance to personal coverage, our team works closely with you to identify the most suitable policies. We assess your current coverage, recommend improvements, and ensure you’re not underinsured or overpaying.",
                "icon" => "FileText",
                "cta" => "Contact us today for professional insurance advice tailored to your needs.",
            ],
        ];

        exit($this->consoleLog($services));
    }
    // == SERVICES ==

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
