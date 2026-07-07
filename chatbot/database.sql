CREATE DATABASE IF NOT EXISTS pentame_chatbot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pentame_chatbot;

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(190) NOT NULL,
  role ENUM('user', 'assistant', 'system') NOT NULL,
  content MEDIUMTEXT NOT NULL,
  raw_response LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_session_created (session_id, created_at)
);

CREATE TABLE IF NOT EXISTS leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(190) NOT NULL,
  name VARCHAR(150) NOT NULL,
  company_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(80) NOT NULL,
  project_type VARCHAR(190) NOT NULL,
  estimated_budget VARCHAR(190) NOT NULL,
  timeline VARCHAR(190) NOT NULL,
  project_description TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_lead_created (created_at)
);

CREATE TABLE IF NOT EXISTS uploaded_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(190) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(190) NULL,
  category VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS chatbot_settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value LONGTEXT NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS faqs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS knowledge_chunks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_title VARCHAR(255) NOT NULL,
  source_url VARCHAR(255) NULL,
  content MEDIUMTEXT NOT NULL,
  content_type VARCHAR(100) NOT NULL,
  updated_at DATETIME NOT NULL,
  FULLTEXT INDEX ft_content (content)
);

INSERT INTO chatbot_settings (setting_key, setting_value, updated_at)
VALUES
  ('chatbot_enabled', '1', NOW()),
  ('sales_email', 'sales@pentame.com', NOW()),
  ('send_lead_email', '0', NOW()),
  ('system_prompt', "You are Pentame's AI Assistant. Only answer questions related to Pentame. Answer professionally. Recommend Pentame services. Suggest relevant pages. Capture leads when appropriate. Never invent company information. If unsure, politely ask the visitor to contact Pentame.", NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW();

INSERT INTO knowledge_chunks (source_title, source_url, content, content_type, updated_at)
VALUES
  ('Home', '/home', 'Pentame offers digital transformation services including website development, ecommerce, mobile apps, SEO, branding, UI/UX, and AI solutions.', 'page', NOW()),
  ('About Us', '/about', 'Pentame is a digital agency focused on business growth through technology, design, and marketing execution.', 'page', NOW()),
  ('Services', '/services', 'Core services include web development, ecommerce development, SEO, digital marketing, AI solutions, and product UI/UX design.', 'page', NOW()),
  ('Portfolio', '/portfolio', 'Pentame portfolio includes agency websites, ecommerce builds, performance optimization, and marketing-driven redesigns.', 'page', NOW()),
  ('Case Studies', '/case-studies', 'Case studies showcase measurable growth in traffic, conversions, speed, and customer engagement.', 'page', NOW()),
  ('Industries', '/industries', 'Pentame supports multiple industries such as retail, healthcare, education, and technology startups.', 'page', NOW()),
  ('Blogs', '/blogs', 'Pentame blog shares insights on AI, SEO, web performance, UX patterns, and digital campaigns.', 'page', NOW()),
  ('Careers', '/careers', 'Careers page lists opportunities for developers, designers, digital marketers, and project managers.', 'page', NOW()),
  ('FAQs', '/faqs', 'Visitors can ask about pricing, timelines, support plans, and project workflows.', 'page', NOW()),
  ('Privacy Policy', '/privacy-policy', 'Pentame respects privacy and handles data securely and responsibly.', 'page', NOW()),
  ('Contact', '/contact', 'Users can contact Pentame through website forms, email, and consultation booking options.', 'page', NOW()),
  ('Company Profile', '/documents/company-profile.pdf', 'Company profile PDF includes mission, services, process, and team overview.', 'document', NOW()),
  ('Brochure', '/documents/brochure.pdf', 'Brochure document introduces Pentame capabilities and engagement models.', 'document', NOW()),
  ('Service Catalogue', '/documents/service-catalogue.pdf', 'Service catalogue lists offerings by category and solution stack.', 'document', NOW()),
  ('Pricing Documents', '/documents/pricing.pdf', 'Pricing document contains indicative pricing ranges and package examples.', 'document', NOW());
