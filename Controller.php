<?php

require_once __DIR__ . '/Functions.php';

class Controller
{
    private $action;

    private $functions;

    private $method;

    private $param;

    private $requestBody;

    private $route;

    public function __construct(array $path, string $method)
    {
        $this->route  = $path[0] ?? null;
        $this->action = @urldecode($path[1]) ?? null;
        $this->param  = @urldecode($path[2]) ?? null;

        $this->method = $method;

        $this->functions = new Functions();

        //Get request body
        switch (True) {
            case ! empty($_POST):
                $this->requestBody = $_POST;
                break;

            case ! empty($_GET):
                $this->requestBody = $_GET;
                break;

            default:
                $this->requestBody = (array) json_decode(file_get_contents("php://input"), true);
        }

        $this->handle();
    }

    private function handle()
    {

        //echo (json_encode($this -> route));
        switch ($this->route) {

            case 'blog': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':

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

                        foreach ($blogPosts as $value) {

                            $blogCategories[] = $value["category"];
                        }
                        die(json_encode([
                            "blogPosts" => $blogPosts,
                            "blogCategories" => $blogCategories
                        ]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "title" => 'Understanding Life Insurance: A Comprehensive Guide for Families',
                            "excerpt" => 'Learn everything you need to know about choosing the right life insurance policy for your family\'s future security and peace of mind.',
                            "author" => 'Dr. Adewale Johnson',
                            "date" => 'February 15, 2026',
                            "readTime" => '5 min read',
                            "category" => 'Life Insurance',
                            "image" => '"https" =>//images.unsplash.com/photo-1769674109078-da12f5cc7871?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsaWZlJTIwaW5zdXJhbmNlJTIwaGFwcHklMjBmYW1pbHl8ZW58MXx8fHwxNzcyMzA1MzkzfDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
                            "content" => `
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
                                `,
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'board_members': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        $this->functions->fetchBoard();
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "name" => "Dr. Johnson",
                            "position" => "Chief Executive Officer",
                            "image" =>
                            "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                            "meta" => [
                                "bio" => "With over 20 years in the insurance industry, Dr. Johnson leads Phindol with vision and expertise.",
                            ],
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'categories': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        die(json_encode([[
                            "id" => 1,
                            "name" => " Technology "
                        ]]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "name" => "Technologia"
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'companies': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        die(json_encode([
                            "name" => "company",
                            "tagline" => "Tagline",
                            "phone" => "+23412345678",
                            "tel" => "+23412345678",
                            "wa" => "+23412345678",
                            "email" => "info@company.ng",
                            "address" => "address",
                            "about" => "Your trusted partner in protecting what matters most, with a commitment to excellence and personalized service.",
                            "hours" => "Mon-Fri: 8:00 AM - 6:00 PM",
                            "social" => [
                                "facebook" => "https://www.facebook.com/",
                                "twitter" => "https://x.com/",
                                "linkedin" => "https://www.linkedin.com/company/",
                                "instagram" => "https://www.instagram.com/",
                                "whatsapp" => "https://wa.me/+234811234567",
                            ]
                        ]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([
                            "name" => "company",
                            "tagline" => "Tagline",
                            "phone" => "+23412345678",
                            "tel" => "+23412345678",
                            "wa" => "+23412345678",
                            "email" => "info@company.ng",
                            "address" => "address",
                            "hours" => "Mon-Fri: 8:00 AM - 6:00 PM",
                            "social" => [
                                "facebook" => "https://www.facebook.com/",
                                "twitter" => "https://x.com/",
                                "linkedin" => "https://www.linkedin.com/company/",
                                "instagram" => "https://www.instagram.com/",
                                "whatsapp" => "https://wa.me/+234811234567",
                            ]
                        ]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'customer_relations': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        die(json_encode([
                            [
                                "id" => 1,
                                "name" => "Opeyemi Abimbola",
                                "position" => "Head Marketing",
                                "image" =>
                                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                                "meta" => [
                                    "bio" => "Serves as Head of Marketing, bringing over 15 years of experience in the insurance industry and customer relationship management. She leads corporate and parastatal engagement efforts, designing targeted campaigns and partnership programs that strengthen client trust and open new business channels.",
                                ],
                            ],
                            [
                                "id" => 2,
                                "name" => "Faith Olasunkanmi Ekundayo",
                                "position" => "Marketing executive",
                                "image" =>
                                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                                "meta" => [
                                    "bio" => "Is a seasoned marketing executive whose extensive background in sales informs every engagement she leads. She excels at translating strategic insights into personalized communication plans that resonate with individual customers, fostering loyalty and advocacy. Faith designs and executes multi‑channel campaigns—ranging from targeted email sequences to social media activations—that consistently boost customer lifetime value and drive measurable growth.",
                                ],
                            ],
                            [
                                "id" => 3,
                                "name" => "M. A. Abdul Rahman",
                                "position" => "Marketing executive",
                                "image" =>
                                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                                "meta" => [
                                    "bio" => "A results-driven marketing executive with a strong focus on understanding customer needs and behaviors. Abdul Rahman designs targeted campaigns—from awareness to post‑purchase engagement—that speak directly to segmented audiences and drive measurable outcomes.",
                                ],
                            ],
                            [
                                "id" => 4,
                                "name" => "Abubakar Yusuf",
                                "position" => "Marketing executive",
                                "image" =>
                                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                                "meta" => [
                                    "bio" => "Abubakar is dedicated to ensuring customer satisfaction at every touchpoint. With expertise in CRM tools and customer advocacy, he proactively addresses client needs, helping businesses build meaningful and lasting relationships with their audience.",
                                ],
                            ],
                            [
                                "id" => 5,
                                "name" => "Beatrice  Orugun",
                                "position" => "Marketing executive",
                                "image" =>
                                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                                "meta" => [
                                    "bio" => "Beatrice is dedicated to deepening customer relationships through engagement and loyalty initiatives. Drawing on detailed market analysis and direct customer feedback, Beatrice crafts programs that reward repeat business and encourage brand advocacy. She oversees the design and rollout of tiered loyalty schemes, personalized email journeys, and targeted in‑app notifications—each element calibrated to drive incremental engagement and measurable uplift in retention rates",
                                ],
                            ],
                            [
                                "id" => 6,
                                "name" => "Sholademi Noah Surudara",
                                "position" => "Digital Marketer",
                                "image" =>
                                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                                "meta" => [
                                    "bio" => "Noah excels at bridging the gap between customer needs and innovative insurance solutions. His professional journey has been defined by a commitment to helping clients understand and access the best products while also driving brand awareness and business growth through strategic digital initiatives.",
                                ],
                            ],
                            [
                                "id" => 7,
                                "name" => "Aniche Lilian",
                                "position" => "Customer/Complaints Representative",
                                "image" =>
                                "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                                "meta" => [
                                    "bio" => "Lilian Aniche is a dedicated Customer and Complaints Representative at Phindol Insurance Brokers Limited, where she plays a key role in ensuring client satisfaction and resolving issues with professionalism and empathy. With a calm demeanor and a strong commitment to service excellence, she works tirelessly to address concerns, streamline communication, and build trust with clients.",
                                ],
                            ],
                        ]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "name" => "Dr. Johnson",
                            "position" => "Chief Executive Officer",
                            "image" =>
                            "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                            "meta" => [
                                "bio" => "With over 20 years in the insurance industry, Dr. Johnson leads Phindol with vision and expertise.",
                            ],
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'gallery': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
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

                        foreach ($galleryImages as $value) {

                            $galleryCategories[] = $value["category"];
                        }

                        die(json_encode(
                            [
                                "galleryImages" => $galleryImages,
                                "galleryCategories" => $galleryCategories
                            ]
                        ));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "url" => "https://images.unsplash.com/photo-1740818576358-7596eb883cf3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbnN1cmFuY2UlMjBtZWV0aW5nJTIwY29uc3VsdGF0aW9ufGVufDF8fHx8MTc3MjMwNzAwM3ww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
                            "title" => "Client Consultation",
                            "category" => "clients",
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'roles':
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        die(json_encode([[
                            "id" => 1,
                            "title" => "CEO & Chairman"
                        ]]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "title" => "CEO & Chairman"
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'services': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        $this->functions->fetchServices();
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "title" => "Risk Assessment/Management",
                            "slug" => "Risk-Assessment-Management",
                            "description" =>
                            "We are committed to helping you safeguard your business and assets with expert risk assessment and management solutions. Our team analyzes potential risks, identifies vulnerabilities, and provides tailored strategies to minimize financial and operational threats. Whether it’s regulatory compliance, business continuity, or loss prevention, we offer proactive solutions to keep you protected.",
                            "icon" => null,
                            "cta" => "Get in touch today to learn more about how we can help.",
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'statistics': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        die(json_encode([[
                            "id" => 1,
                            "label" => "Clients Served",
                            "value" => 250,
                        ]]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "label" => "Clients Served",
                            "value" => 250,
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'team_members': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        $this->functions->fetchManagement();
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "name" => "Dr. Johnson",
                            "position" => "Chief Executive Officer",
                            "image" =>
                            "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                            "meta" => [
                                "bio" => "With over 20 years in the insurance industry, Dr. Johnson leads Phindol with vision and expertise.",
                            ],
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'testimonials': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        die(json_encode([[
                            "id" => 1,
                            "name" => 'Oluwaseun Adeyemi',
                            "location" => 'Abuja',
                            "text" => 'Phindol Insurance made the process of securing our corporate insurance seamless. Their team is professional, responsive, and genuinely cares about our needs.',
                            "rating" => 5,
                        ]]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "name" => 'Oluwaseun Adeyemi',
                            "company" => 'TechCorp Nigeria',
                            "text" => 'Phindol Insurance made the process of securing our corporate insurance seamless. Their team is professional, responsive, and genuinely cares about our needs.',
                            "rating" => 5,
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'users':
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        die(json_encode([[
                            "id" => 1,
                            "name" => "Sarah Johnson",
                            "email" => "sarah.johnson@techcorp.com",
                            "image_url" => "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400",
                            "role_id" => 1,
                            "department" => "Executive"
                        ]]));
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "name" => "Sarah Johnson",
                            "email" => "sarah.johnson@techcorp.com",
                            "image_url" => "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400",
                            "role_id" => 1,
                            "department" => "Executive"
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'partners': //
                switch ($this->method) {
                    case 'DELETE':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'GET':
                        $this->functions->fetchPartners();
                        break;

                    case 'POST':
                        switch ($k = rand()) {
                            case $k % 5 == 1:
                                die(json_encode(
                                    ["status" => '200', "id" => $k]
                                ));
                                break;

                            default:
                                http_response_code(402);
                                die(json_encode(
                                    ["status" => '402']
                                ));
                                break;
                        }
                        break;

                    case 'PUT':
                        die(json_encode([[
                            "id" => 1,
                            "name" => "Dr. Johnson",
                            "position" => "Chief Executive Officer",
                            "image" =>
                            "https://images.unsplash.com/photo-1560250097-0b93528c311a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBidXNpbmVzcyUyMG1hbiUyMGFmcmljYW58ZW58MXx8fHwxNzI0MjYzNzA4fDA&ixlib=rb-4.1.0&q=80&w=1080",
                            "meta" => [
                                "bio" => "With over 20 years in the insurance industry, Dr. Johnson leads Phindol with vision and expertise.",
                            ],
                        ]]));
                        break;

                    default:
                        $this->methodNotAllowed(["GET", "POST", "PUT", "DELETE"]);
                        break;
                }
                break;

            case 'ping': //
                $this->functions->ping();
                break;

            default:
                //$this -> functions -> fetchProduct();
                $this->endpointNotFound(['/ping']);
        }
    }

    private function methodNotAllowed(array $allowed): void
    {
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowed));
        echo json_encode([
            'error' => 'Method Not Allowed',
            'allowed' => $allowed
        ]);
    }

    private function endpointNotFound(array $allinks = [], int $level = 0): void
    {

        $res = [
            [
                "header" => 'HTTP/1.1 404 Not Found',
                "rescode" => 404,
                "message" => [
                    "message" => 'Page not Found',
                    'Allowed' => $allinks,
                ],
            ],
            [
                "header" => 'HTTP/1.1 400 Bad Request',
                "rescode" => 400,
                "message" => [
                    'message' => 'Bad Request',
                    'Allowed' => $allinks,
                ],
            ],
        ];

        header($res[$level]['header']);
        //http_response_code ($res[$level]['rescode']);

        echo json_encode($res[$level]['message']);
    }
}
