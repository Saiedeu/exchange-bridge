-- ================================================================
-- Exchange Bridge Database Schema
-- Compatible with MySQL 5.7+ and MariaDB 10.2+
-- Character Set: UTF8MB4 for full Unicode support
-- ================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for table `banned_ips`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `banned_ips`;
CREATE TABLE `banned_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `banned_by` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `blog_posts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `currencies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `background_class` varchar(100) DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `payment_address` varchar(255) DEFAULT NULL,
  `address_label` varchar(100) DEFAULT NULL,
  `address_type` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample currency data
-- --------------------------------------------------------

INSERT INTO `currencies` (`code`, `name`, `display_name`, `logo`, `background_class`, `icon_class`, `payment_address`, `address_label`, `address_type`, `status`) VALUES
('BDT', 'bKash BDT', 'BDT', NULL, 'bg-pink-100 text-pink-500', 'fas fa-money-bill-wave', NULL, 'bKash Mobile Number', 'mobile', 'active'),
('USD', 'PayPal USD', 'USD', NULL, 'bg-blue-500 text-white', 'fab fa-paypal', NULL, 'PayPal Email Address', 'email', 'active'),
('BTC', 'Bitcoin BTC', 'BTC', NULL, 'bg-yellow-500 text-white', 'fab fa-bitcoin', NULL, 'Bitcoin Wallet Address', 'wallet', 'active');

-- --------------------------------------------------------
-- Table structure for table `currency_rates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `currency_rates`;
CREATE TABLE `currency_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_code` varchar(10) NOT NULL,
  `we_buy_rate` decimal(10,2) DEFAULT NULL,
  `we_sell_rate` decimal(10,2) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_currency` (`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `exchanges`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `exchanges`;
CREATE TABLE `exchanges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_id` varchar(20) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `payment_address` varchar(255) NOT NULL,
  `from_currency` varchar(10) NOT NULL,
  `to_currency` varchar(10) NOT NULL,
  `send_amount` decimal(20,8) NOT NULL,
  `receive_amount` decimal(20,8) NOT NULL,
  `exchange_rate` decimal(20,8) NOT NULL,
  `status` enum('pending','confirmed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_id` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `exchange_rates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE `exchange_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_currency` varchar(10) NOT NULL,
  `to_currency` varchar(10) NOT NULL,
  `rate` decimal(20,8) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `display_on_homepage` tinyint(1) DEFAULT 0,
  `we_buy` decimal(15,8) DEFAULT 0.00000000,
  `we_sell` decimal(15,8) DEFAULT 0.00000000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_pair` (`from_currency`,`to_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample exchange rates
-- --------------------------------------------------------

INSERT INTO `exchange_rates` (`from_currency`, `to_currency`, `rate`, `status`, `display_on_homepage`, `we_buy`, `we_sell`) VALUES
('USD', 'BDT', 120.00000000, 'active', 1, 118.00000000, 122.00000000),
('BDT', 'USD', 0.00833333, 'active', 1, 0.00820000, 0.00847000);

-- --------------------------------------------------------
-- Table structure for table `floating_buttons`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `floating_buttons`;
CREATE TABLE `floating_buttons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `button_type` varchar(20) DEFAULT 'custom',
  `title` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `custom_icon` varchar(255) DEFAULT '',
  `color` varchar(7) NOT NULL DEFAULT '#25D366',
  `url` text NOT NULL,
  `target` varchar(10) NOT NULL DEFAULT '_blank',
  `position` varchar(20) NOT NULL DEFAULT 'right',
  `order_index` int(11) NOT NULL DEFAULT 0,
  `status` varchar(10) NOT NULL DEFAULT 'active',
  `show_on_mobile` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_desktop` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `media`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `caption` text DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `file_type` (`file_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notices`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notices`;
CREATE TABLE `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('scrolling','popup') NOT NULL,
  `position` int(11) DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `pages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` mediumtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample pages
-- --------------------------------------------------------

INSERT INTO `pages` (`slug`, `title`, `content`, `meta_title`, `meta_description`, `status`) VALUES
('about', 'About Us', '<h3><strong>Our Introduction</strong></h3><p><strong>Exchange Bridge</strong> is one of the most innovative and reliable international payment exchange service providers in Bangladesh. We provide such technology-dependent solutions for freelancers, remote workers, digital entrepreneurs and online business people in Bangladesh, through which they can transfer money very easily from PayPal, Payoneer, Skrill, Wise, and other international payment platforms to Bangladeshi mobile banking or bank accounts.</p>', 'About Us - Exchange Bridge', 'Fast, secure and transparent international payment exchange service for freelancers in Bangladesh. Money transfer support from PayPal, Payoneer, Skrill.', 'active'),
('privacy', 'Privacy Policy', '<p>We ensure the highest security of your personal and financial information. Learn about Exchange Bridge privacy policy and our data usage rules.</p>', 'Privacy Policy - Exchange Bridge', 'We ensure the highest security of your personal and financial information. Learn about Exchange Bridge privacy policy and our data usage rules.', 'active'),
('faq', 'Frequently Asked Questions', '<h3>What is Exchange Bridge?</h3><p>We are a platform that provides you with the facility to exchange various currencies and payment methods quickly and securely.</p>', 'FAQ - Exchange Bridge', 'Frequently asked questions about Exchange Bridge services, processes, and policies.', 'active'),
('terms', 'Terms and Conditions', '<p>Read our terms and conditions to understand the rules and regulations for using our services.</p>', 'Terms and Conditions - Exchange Bridge', 'Read our terms and conditions to understand the rules and regulations for using our services.', 'active'),
('contact', 'Contact Us', '<p>We would love to hear from you! Please feel free to contact us using the form or the contact details below.</p>', 'Contact Us - Exchange Bridge', 'Get in touch with us for any questions or support. We are here to help you.', 'active');

-- --------------------------------------------------------
-- Table structure for table `rate_limits`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate_key` varchar(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rate_key` (`rate_key`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `reserves`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reserves`;
CREATE TABLE `reserves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_code` varchar(10) NOT NULL,
  `amount` decimal(20,8) NOT NULL,
  `min_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `max_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `auto_update` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_code` (`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample reserves
-- --------------------------------------------------------

INSERT INTO `reserves` (`currency_code`, `amount`, `min_amount`, `max_amount`, `auto_update`) VALUES
('USD', 100000.00000000, 5.00000000, 5000.00000000, 1),
('BDT', 10000000.00000000, 500.00000000, 500000.00000000, 1);

-- --------------------------------------------------------
-- Table structure for table `security_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `ip_address` (`ip_address`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Essential settings
-- --------------------------------------------------------

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Exchange Bridge'),
('site_tagline', 'Exchange Taka Globally'),
('primary_color', '#33ce46'),
('secondary_color', '#64c440'),
('header_color', '#285ff4'),
('footer_color', '#005b6b'),
('operator_status', 'online'),
('working_hours', '9 am-11.50pm'),
('contact_phone', '+880171234566'),
('contact_whatsapp', '+880171234566'),
('contact_email', 'contact@example.com'),
('contact_address', 'Your Address'),
('seo_meta_title', 'Exchange Bridge - Fast Currency Exchange'),
('seo_meta_description', 'Exchange Bridge offers fast and secure currency exchange services globally.'),
('seo_meta_keywords', 'currency exchange, taka exchange, paypal, bkash, bitcoin'),
('enable_notification_sound', 'yes'),
('default_payment_address', '01712345678'),
('wizard_title', 'Fast Exchange in Minutes'),
('wizard_subtitle', 'Minimum Exchange $5 Dollar'),
('wizard_heading', 'Start Exchange'),
('wizard_footer_text', 'When ordering, give your mobile phone number and when you buy dollars from us, you must send money from your bKash/rocket/cash number. If you send money from other number, your order will be canceled and the rest of the money will be refunded.'),
('contact_step_title', 'Provide Your Contact Details'),
('confirmation_title', 'Confirm Your Exchange'),
('send_section_label', 'SEND'),
('receive_section_label', 'RECEIVE'),
('currency_select_label', 'Select Exchange Currency'),
('amount_input_label', 'Enter Exchange Amount'),
('receive_amount_label', 'You will Get this Amount'),
('continue_button_text', 'Continue to Next Step'),
('name_field_label', 'Full Name'),
('email_field_label', 'Email Address'),
('phone_field_label', 'Phone Number'),
('address_field_label', 'Payment Address'),
('address_help_text', 'This is the address where you will receive the amount.'),
('back_button_text', 'Back'),
('continue_step2_text', 'Continue'),
('reference_id_title', 'Exchange Reference ID'),
('reference_id_message', 'Save this "Reference ID" to track your exchange status. It will be needed when contacting us.'),
('exchange_details_title', 'Exchange Details'),
('payment_details_title', 'Payment Details'),
('payment_instruction', 'Send {amount} BDT/USD to this account to start your transaction:'),
('after_payment_message', 'After sending payment, contact our operator on WhatsApp with your "Reference ID".'),
('next_steps_title', 'Next Steps'),
('whatsapp_contact_message', 'Contact our operator on WhatsApp to complete your exchange order:'),
('whatsapp_button_text', 'Contact Operator'),
('final_instruction', 'Give your reference ID when contacting and follow the operator instructions to complete the transaction.'),
('view_receipt_text', 'View Receipt'),
('complete_button_text', 'Complete'),
('next_todo_text', 'Next To Do'),
('send_label_text', 'You Send:'),
('receive_label_text', 'You Receive:'),
('rate_label_text', 'Exchange Rate:'),
('datetime_label_text', 'Date and Time:'),
('status_label_text', 'Status:'),
('pending_status', 'Pending'),
('confirmed_status', 'Confirmed'),
('cancelled_status', 'Cancelled'),
('min_amount_error', 'Minimum 5 dollars exchange required'),
('invalid_email_error', 'Please provide correct email address'),
('required_fields_error', 'Please fill all required fields'),
('rate_unavailable_error', 'Exchange rate not available for this currency'),
('amount_required_error', 'Please enter exchange amount'),
('exchange_success_message', 'Your exchange order has been submitted successfully!'),
('copy_success_message', 'Account number copied to clipboard'),
('wizard_font_family', 'hind_siliguri'),
('wizard_primary_color', '#5dffde'),
('wizard_progress_bar_color', '#285FB7'),
('wizard_border_radius', 'medium'),
('enable_animations', 'yes'),
('minimum_exchange_amount', '5'),
('site_timezone', 'Asia/Dhaka'),
('logo_type', 'text'),
('site_logo_text', 'Exchange Bridge');

-- --------------------------------------------------------
-- Table structure for table `testimonials`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rating` int(1) NOT NULL DEFAULT 5,
  `message` text DEFAULT NULL,
  `from_currency` varchar(10) DEFAULT NULL,
  `to_currency` varchar(10) DEFAULT NULL,
  `status` enum('pending','active','inactive') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','editor') NOT NULL DEFAULT 'editor',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Default admin user (password: admin123)
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
('admin', 'admin@exchangebridge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- --------------------------------------------------------
-- Re-enable foreign key checks
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Useful indexes for performance
-- --------------------------------------------------------

ALTER TABLE `exchanges` ADD INDEX `idx_status` (`status`);
ALTER TABLE `exchanges` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `exchanges` ADD INDEX `idx_currencies` (`from_currency`, `to_currency`);
ALTER TABLE `exchange_rates` ADD INDEX `idx_status` (`status`);
ALTER TABLE `settings` ADD INDEX `idx_setting_key` (`setting_key`);

-- --------------------------------------------------------
-- Database setup completed successfully
-- --------------------------------------------------------