CREATE DATABASE IF NOT EXISTS talent_pool;
USE talent_pool;

SET FOREIGN_KEY_CHECKS = 0;

-- GROUP 1: APPLICANT SYSTEM
CREATE TABLE IF NOT EXISTS applicants (
    applicant_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    phone_number VARCHAR(20),
    email VARCHAR(100),
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    place_of_birth VARCHAR(100),
    applicant_status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    is_talent BOOLEAN DEFAULT FALSE,
    INDEX idx_email (email),
    INDEX idx_status (applicant_status)
);

CREATE TABLE IF NOT EXISTS applicant_accounts (
    account_id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT UNIQUE NOT NULL,
    username VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    account_status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    INDEX idx_username (username)
);

CREATE TABLE IF NOT EXISTS applicant_profiles (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT UNIQUE NOT NULL,
    cv_url VARCHAR(500),
    portfolio_url VARCHAR(500),
    profile_picture_url VARCHAR(500),
    meta_title VARCHAR(200),
    meta_description TEXT,
    profile_views INT DEFAULT 0,
    application_count INT DEFAULT 0,
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE
);

-- GROUP 2: COMPANY SYSTEM
CREATE TABLE IF NOT EXISTS companies (
    company_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(150) NOT NULL,
    brand_name VARCHAR(100),
    short_name VARCHAR(50),
    company_overview TEXT,
    industry VARCHAR(100),
    description TEXT,
    logo_url VARCHAR(500),
    is_VIP BOOLEAN DEFAULT FALSE,
    INDEX idx_company_name (company_name),
    INDEX idx_industry (industry)
);

CREATE TABLE IF NOT EXISTS company_accounts (
    account_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT UNIQUE NOT NULL,
    username VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    role ENUM('company_admin', 'company_user') DEFAULT 'company_user',
    account_status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    INDEX idx_username (username)
);

CREATE TABLE IF NOT EXISTS company_contact (
    contact_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    hr_email VARCHAR(100),
    updated_by_user_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_reviews INT DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS company_locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    headquarter VARCHAR(200),
    city VARCHAR(100),
    country VARCHAR(100),
    contact_address TEXT,
    website_url VARCHAR(500),
    facebook_url VARCHAR(500),
    linkedin_url VARCHAR(500),
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS company_timeline (
    timeline_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    founded_year YEAR,
    active_jobs_count INT DEFAULT 0,
    total_jobs_posted INT DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS company_experience (
    experience_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    slug VARCHAR(100),
    cover_image_url VARCHAR(500),
    meta_title VARCHAR(200),
    meta_description TEXT,
    total_views INT DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

-- GROUP 3: EDUCATION SYSTEM
CREATE TABLE IF NOT EXISTS universities (
    university_id INT PRIMARY KEY AUTO_INCREMENT,
    university_name VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS majors (
    major_id INT PRIMARY KEY AUTO_INCREMENT,
    university_id INT NOT NULL,
    major_name VARCHAR(100) NOT NULL,
    major_description TEXT,
    start_time DATE,
    certificate VARCHAR(100),
    gpa DECIMAL(3,2),
    FOREIGN KEY (university_id) REFERENCES universities(university_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS learn (
    applicant_id INT,
    major_id INT,
    PRIMARY KEY (applicant_id, major_id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    FOREIGN KEY (major_id) REFERENCES majors(major_id) ON DELETE CASCADE
);

-- GROUP 4: JOBS & APPLICATIONS
CREATE TABLE IF NOT EXISTS jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    job_name VARCHAR(150) NOT NULL,
    job_description TEXT,
    average_salary DECIMAL(15,2),
    field VARCHAR(100),
    INDEX idx_field (field)
);

CREATE TABLE IF NOT EXISTS job_applicant (
    applicant_id INT,
    job_id INT,
    priority INT DEFAULT 1,
    PRIMARY KEY (applicant_id, job_id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE
);

-- GROUP 5: HUMAN RESOURCES
CREATE TABLE IF NOT EXISTS employees (
    employees_id INT PRIMARY KEY AUTO_INCREMENT,
    role VARCHAR(100),
    assigned_id INT
);

-- GROUP 6: PROJECTS & TASKS
CREATE TABLE IF NOT EXISTS projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    project_name VARCHAR(150) NOT NULL,
    project_time DATE,
    url VARCHAR(500),
    compensation DECIMAL(15,2),
    progress_percentage INT DEFAULT 0 CHECK (progress_percentage >= 0 AND progress_percentage <= 100),
    request_description TEXT,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS project_talents (
    applicant_id INT,
    company_id INT,
    project_id INT,
    PRIMARY KEY (applicant_id, project_id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tasks (
    task_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    task_name VARCHAR(150) NOT NULL,
    task_description TEXT,
    start_time DATETIME,
    end_time DATETIME,
    deadline DATETIME,
    task_role VARCHAR(100),
    task_status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_deadline (deadline),
    INDEX idx_status (task_status)
);

-- GROUP 7: EVENTS & NETWORKING
CREATE TABLE IF NOT EXISTS connect_events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(150),
    employees_id INT,
    event_date DATE,
    notes TEXT,
    event_format ENUM('online', 'offline', 'hybrid') DEFAULT 'online',
    location VARCHAR(200),
    expertise VARCHAR(100),
    FOREIGN KEY (employees_id) REFERENCES employees(employees_id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date)
);

CREATE TABLE IF NOT EXISTS event_registrations (
    applicant_id INT,
    event_id INT,
    session_starttime DATETIME,
    session_endtime DATETIME,
    reason_for_joining TEXT,
    PRIMARY KEY (applicant_id, event_id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES connect_events(event_id) ON DELETE CASCADE
);

-- GROUP 8: SOCIAL FEATURES
CREATE TABLE IF NOT EXISTS posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NULL,
    applicant_id INT NULL,
    content TEXT NOT NULL,
    post_type ENUM('job_announcement', 'company_news', 'personal_update') DEFAULT 'personal_update',
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    CONSTRAINT chk_author CHECK ((company_id IS NOT NULL AND applicant_id IS NULL) OR (company_id IS NULL AND applicant_id IS NOT NULL))
);

-- GROUP 9: MEETINGS & COLLABORATION
CREATE TABLE IF NOT EXISTS meetings (
    meeting_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    meeting_title VARCHAR(150) NOT NULL,
    meeting_time DATETIME NOT NULL,
    meeting_file VARCHAR(200),
    meeting_location VARCHAR(200),
    meeting_url VARCHAR(500),
    scheduled_time DATETIME,
    meeting_description TEXT,
    notes TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE SET NULL,
    INDEX idx_meeting_time (meeting_time)
);

CREATE TABLE IF NOT EXISTS meeting_attendees (
    meeting_id INT,
    applicant_id INT,
    status ENUM('invited', 'confirmed', 'attended', 'absent') DEFAULT 'invited',
    PRIMARY KEY (meeting_id, applicant_id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(meeting_id) ON DELETE CASCADE,
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE
);

-- GROUP 10: TASKING & EVALUATION
CREATE TABLE IF NOT EXISTS task_skills (
    task_id INT,
    skill_name VARCHAR(100),
    required_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    PRIMARY KEY (task_id, skill_name),
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    INDEX idx_skill (skill_name)
);

CREATE TABLE IF NOT EXISTS evaluations (
    evaluation_id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT,
    applicant_id INT NOT NULL,
    evaluation_content TEXT,
    reviewer INT,
    category VARCHAR(100),
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE SET NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    INDEX idx_applicant (applicant_id)
);

-- GROUP 11: TALENTS & CATEGORIZATION
CREATE TABLE IF NOT EXISTS talents (
    talent_id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT UNIQUE NOT NULL,
    nickname VARCHAR(100),
    total_projects INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    INDEX idx_rating (rating)
);

CREATE TABLE IF NOT EXISTS tags (
    tag_id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(100) NOT NULL UNIQUE,
    tag_description TEXT,
    INDEX idx_name (tag_name)
);

-- GROUP 12: PLATFORM FEATURES
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('job_alert', 'application_update', 'system_notification') DEFAULT 'system_notification',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    scheduled_time TIMESTAMP,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE,
    INDEX idx_applicant (applicant_id),
    INDEX idx_read (is_read)
);

CREATE TABLE IF NOT EXISTS skills (
    skill_id INT PRIMARY KEY AUTO_INCREMENT,
    skill_name VARCHAR(100) NOT NULL UNIQUE,
    skill_category VARCHAR(100),
    description TEXT
);

-- PHẦN THÊM DỮ LIỆU MẪU --
TRUNCATE TABLE applicants;
INSERT INTO applicants (full_name, date_of_birth, phone_number, email, gender, address, place_of_birth, applicant_status, is_talent) VALUES
('Nguyễn Văn Nam', '1997-05-12', '0912345678', 'nam.nguyen@email.com', 'male', 'Q1, TP.HCM', 'Hà Nội', 'active', 1),
('Trần Thị Mai', '1999-10-25', '0987123456', 'mai.tran@email.com', 'female', 'Đống Đa, HN', 'Đà Nẵng', 'active', 0),
('Phạm Minh Tuấn', '1995-03-07', '0902123456', 'tuan.pham@email.com', 'male', 'Thanh Xuân, HN', 'TP.HCM', 'pending', 0),
('Lê Hoàng Hải', '1996-12-18', '0899123456', 'hai.le@email.com', 'male', 'B.Thạnh, TP.HCM', 'Huế', 'inactive', 1),
('Ngô Quỳnh Anh', '1998-02-22', '0911987654', 'anh.ngo@email.com', 'female', 'Cầu Giấy, HN', 'Nam Định', 'active', 1),
('Đoàn Văn Bảo', '1994-11-10', '0977222333', 'bao.doan@email.com', 'male', 'Hai Bà Trưng, HN', 'Bắc Ninh', 'active', 0),
('Bùi Trần Thúy', '2000-06-15', '0918779913', 'thuy.bui@email.com', 'female', 'Q5, TP.HCM', 'Cần Thơ', 'active', 0);

TRUNCATE TABLE applicant_accounts;
INSERT INTO applicant_accounts (applicant_id, username, password, role) VALUES
(1, 'nguyenvannam', 'matkhau1', 'user'),
(2, 'tranthimai', 'matkhau2', 'user'),
(3, 'phamtuantp', 'matkhau3', 'admin'),
(4, 'lehoanghai', 'matkhau4', 'user'),
(5, 'ngoqanh', 'matkhau5', 'user'),
(6, 'doanvanbao', 'matkhau6', 'user'),
(7, 'buitranthuy', 'matkhau7', 'user');

TRUNCATE TABLE applicant_profiles;
INSERT INTO applicant_profiles (applicant_id, cv_url, portfolio_url, profile_picture_url, meta_title, meta_description, profile_views, application_count) VALUES
(1, 'https://cv.com/namnv.pdf', 'https://portfolio.com/namnv', 'https://randomuser.me/api/portraits/men/1.jpg', 'Backend Developer', '5 năm backend Java/Spring', 95, 10),
(2, 'https://cv.com/maitt.pdf', NULL, 'https://randomuser.me/api/portraits/women/2.jpg', 'Data Analyst', 'Đam mê dữ liệu & kinh doanh', 48, 3),
(3, 'https://cv.com/tuantp.pdf', 'https://behance.net/tuantp', 'https://randomuser.me/api/portraits/men/3.jpg', 'UI Designer', 'Thiết kế UI web/app', 66, 12),
(4, 'https://cv.com/halh.pdf', 'https://dribbble.com/halh', 'https://randomuser.me/api/portraits/men/4.jpg', 'QA Tester', 'Tự động hóa kiểm thử', 35, 4),
(5, 'https://cv.com/anhngo.pdf', NULL, 'https://randomuser.me/api/portraits/women/5.jpg', 'Content Creator', 'Viết content marketing', 102, 9),
(6, 'https://cv.com/bao.dv.pdf', NULL, 'https://randomuser.me/api/portraits/men/6.jpg', 'Java Dev', 'Java/Spring/AWS', 14, 1),
(7, 'https://cv.com/thuybt.pdf', NULL, 'https://randomuser.me/api/portraits/women/7.jpg', 'HR Staff', 'Nhân sự, tuyển dụng', 7, 0);

TRUNCATE TABLE companies;
INSERT INTO companies (company_name, brand_name, short_name, company_overview, industry, description, logo_url, is_VIP) VALUES
('Công ty Cổ phần Công nghệ Việt', 'VietTech', 'VT', 'Phát triển phần mềm', 'Công nghệ thông tin', 'Sản xuất phần mềm, dịch vụ số', 'https://img.logo.net/avatars/viettech.png', 1),
('Công ty Sức Khỏe Số', 'HealthPlus', 'HP', 'Y tế số hóa', 'Y tế', 'Chăm sóc sức khỏe online', 'https://img.logo.net/avatars/healthplus.png', 0),
('Công ty TNHH Logistics An Bình', 'AnBinhLog', 'ABL', 'Logistics toàn quốc', 'Logistics', 'Vận chuyển, chuyển phát nhanh', 'https://img.logo.net/avatars/anbinhlog.png', 0),
('Công ty Kiến trúc Đông Á', 'DongAArc', 'DAA', 'Thiết kế kiến trúc', 'Xây dựng', 'Thiết kế biệt thự/văn phòng', 'https://img.logo.net/avatars/dongaarc.png', 0),
('Công ty TM-DV Vạn Lộc', 'VanLoc', 'VL', 'Thương mại điện tử đa ngành', 'Bán lẻ', 'Bán lẻ TMĐT', 'https://img.logo.net/avatars/vanloc.png', 0);

TRUNCATE TABLE company_accounts;
INSERT INTO company_accounts (company_id, username, password, role) VALUES
(1, 'viettechadmin', 'mkvt123', 'company_admin'),
(2, 'healthplususer', 'mkhp123', 'company_user'),
(3, 'anbinhadmin', 'mka123', 'company_admin'),
(4, 'dongaarcuser', 'mkda999', 'company_user'),
(5, 'vanlocadmin', 'mkvl555', 'company_admin');

TRUNCATE TABLE jobs;
INSERT INTO jobs (job_name, job_description, average_salary, field) VALUES
('Backend Developer', 'Phát triển API backend', 15000000, 'CNTT'),
('Data Analyst', 'Phân tích dữ liệu', 12000000, 'Data'),
('UI Designer', 'Thiết kế giao diện web/app', 11000000, 'Design'),
('Tester', 'Kiểm thử phần mềm', 9500000, 'CNTT'),
('HR Staff', 'Nhân sự, tuyển dụng', 10000000, 'Quản trị');

TRUNCATE TABLE job_applicant;
INSERT INTO job_applicant (applicant_id, job_id, priority) VALUES
(1, 1, 1), (2, 2, 2), (3, 3, 2), (4, 4, 1), (5, 2, 1), (6, 1, 1), (7, 5, 1);

TRUNCATE TABLE skills;
INSERT INTO skills (skill_name, skill_category, description) VALUES
('Java', 'Technical', 'Ngôn ngữ lập trình backend phổ biến'),
('SQL', 'Technical', 'Quản lý CSDL quan hệ'),
('UI Design', 'Technical', 'Thiết kế giao diện'),
('Data Analysis', 'Technical', 'Phân tích dữ liệu'),
('Communication', 'Soft skill', 'Giao tiếp và thuyết trình'),
('Testing', 'Technical', 'Kiểm thử phần mềm'),
('Content Writing', 'Soft skill', 'Viết nội dung sáng tạo');

TRUNCATE TABLE company_contact;
INSERT INTO company_contact (company_id, average_rating, contact_email, contact_phone, hr_email, total_reviews) VALUES
(1, 4.5, 'contact@viettech.com', '02888886661', 'hr@viettech.com', 35),
(2, 4.2, 'info@healthplus.vn', '02422334477', 'tuyendung@healthplus.vn', 20),
(3, 4.1, 'sales@anbinhlog.vn', '0289992234', 'hr@anbinhlog.vn', 10),
(4, 3.9, 'hello@dongaarc.com', '0289321453', 'hr@dongaarc.com', 5),
(5, 4.0, 'cskh@vanloc.vn', '0222223222', 'hr@vanloc.vn', 4);

TRUNCATE TABLE company_locations;
INSERT INTO company_locations (company_id, headquarter, city, country, contact_address, website_url, facebook_url, linkedin_url) VALUES
(1, 'Landmark 81', 'TP.HCM', 'Việt Nam', 'Tầng 20, Landmark 81, Bình Thạnh', 'https://viettech.com', 'https://fb.com/viettech', 'https://linkedin.com/company/viettech'),
(2, 'Keangnam Building', 'Hà Nội', 'Việt Nam', 'Tầng 17, Keangnam', 'https://healthplus.vn', 'https://fb.com/healthplus', 'https://linkedin.com/company/healthplus'),
(3, 'Nhà máy Bình Dương', 'Bình Dương', 'Việt Nam', 'Lô D13, KCN VSIP', NULL, NULL, NULL),
(4, '121 Đội Cấn', 'Hà Nội', 'Việt Nam', '121 Đội Cấn, Ba Đình', 'https://dongaarc.com', NULL, NULL),
(5, 'Ninh Kiều', 'Cần Thơ', 'Việt Nam', '45B Nguyễn Trãi, Ninh Kiều', 'https://vanloc.vn', NULL, NULL);

TRUNCATE TABLE company_timeline;
INSERT INTO company_timeline (company_id, founded_year, active_jobs_count, total_jobs_posted) VALUES
(1, 2017, 3, 10), (2, 2019, 4, 8), (3, 2012, 2, 5), (4, 2021, 1, 1), (5, 2015, 2, 7);

TRUNCATE TABLE universities;
INSERT INTO universities (university_name) VALUES
('Đại học Bách Khoa Hà Nội'), ('Đại học Quốc gia TP.HCM'), ('Đại học Ngoại thương'), ('Đại học Kinh tế Quốc dân'),
('Đại học Bách Khoa Đà Nẵng'), ('Đại học Sư phạm Hà Nội'), ('Đại học Khoa học Tự nhiên TP.HCM'), ('Đại học Cần Thơ'),
('Đại học Giao thông Vận tải'), ('Đại học Dược Hà Nội'), ('Đại học Y Hà Nội'), ('Đại học Kiến trúc TP.HCM'),
('Đại học Khoa học Xã hội và Nhân văn Hà Nội'), ('Đại học Công nghệ Thông tin TP.HCM'), ('Đại học Tài chính - Marketing'),
('Đại học FPT'), ('Đại học Hoa Sen'), ('Đại học Thương mại'), ('Đại học Luật Hà Nội'), ('Đại học Công Nghiệp Hà Nội'),
('Đại học Thủy lợi'), ('Đại học Sư phạm Kỹ thuật TP.HCM'), ('Đại học Mở TP.HCM'), ('Đại học Công nghiệp TP.HCM'),
('Đại học Hải Phòng'), ('Đại học Mỹ thuật Công nghiệp'), ('Đại học Bách khoa TP.HCM'), ('Đại học Kiến trúc Hà Nội'),
('Đại học Điện lực'), ('Đại học Đà Lạt'), ('Đại học Nông Lâm TP.HCM'), ('Đại học Kinh tế Luật TP.HCM'),
('Đại học Giao thông Vận tải TP.HCM'), ('Đại học Tôn Đức Thắng'), ('Đại học Tài nguyên và Môi trường'),
('Đại học Công nghệ Thông tin và Truyền thông Thái Nguyên'), ('Đại học Ngoại ngữ - Đại học Quốc gia Hà Nội'),
('Đại học Quốc tế - Đại học Quốc gia TP.HCM'), ('Đại học Văn hóa Hà Nội'), ('Đại học Sân khấu Điện ảnh TP.HCM');

TRUNCATE TABLE company_experience;
INSERT INTO company_experience (company_id, slug, cover_image_url, meta_title, meta_description, total_views) VALUES
(1, 'viettech-lead-2023', NULL, 'Dẫn đầu giải thưởng startup 2023', 'VietTech vươn tầm quốc tế', 506),
(2, 'healthplus-digitalcare', NULL, 'TOP dịch vụ y tế số', 'Ứng dụng HealthPlus dẫn đầu', 410),
(3, 'anbinh-fastlog', NULL, 'Hệ thống vận chuyển toàn quốc', 'Vận chuyển 63 tỉnh thành', 92);

TRUNCATE TABLE majors;
INSERT INTO majors (university_id, major_name, major_description, start_time, certificate, gpa) VALUES
(1, 'Khoa học máy tính', 'Lập trình, thiết kế hệ thống', '2016-08-01', 'Kỹ sư', 3.55),
(2, 'Kinh Tế Quốc tế', 'Thương mại toàn cầu', '2018-08-01', 'Cử nhân', 3.61),
(3, 'Thiết kế đồ họa', 'Multimedia Design', '2015-08-01', 'Cử nhân', 3.40),
(4, 'Quản trị nhân sự', 'Quản lý và phát triển nhân lực', '2017-09-01', 'Cử nhân', 3.72);

TRUNCATE TABLE learn;
INSERT INTO learn (applicant_id, major_id) VALUES
(1,1),(2,2),(3,3),(4,1),(5,4),(6,1),(7,2);

TRUNCATE TABLE employees;
INSERT INTO employees (role, assigned_id) VALUES
('Quản lý sự kiện', 1), ('Nhân sự tuyển dụng', 2), ('QA Manager', 4), ('Phụ trách IT', 5), ('Nhân viên truyền thông', 7);

TRUNCATE TABLE projects;
INSERT INTO projects (company_id, project_name, project_time, url, compensation, progress_percentage, request_description) VALUES
(1, 'Nền tảng tuyển dụng thông minh', '2024-01-10', 'https://viettech.com/job-platform', 35000000, 70, 'Phát triển hệ thống tuyển dụng tự động AI'),
(2, 'App tư vấn sức khỏe', '2023-08-05', 'https://healthplus.vn/advice', 28500000, 95, 'Tư vấn sức khỏe online qua chat'),
(3, 'CRM vận tải', '2024-04-15', NULL, 22000000, 30, 'Quản lý khách hàng, hợp đồng logistics'),
(5, 'Website TMĐT Vạn Lộc', '2023-10-21', 'https://vanloc.vn', 26000000, 90, 'Website bán hàng đa kênh');

TRUNCATE TABLE project_talents;
INSERT INTO project_talents (applicant_id, company_id, project_id) VALUES
(1, 1, 1), (2, 2, 2), (4, 3, 3), (7, 5, 4), (5, 5, 4);

TRUNCATE TABLE tasks;
INSERT INTO tasks (project_id, task_name, task_description, start_time, end_time, deadline, task_role, task_status) VALUES
(1, 'Xây dựng API tuyển dụng', 'Triển khai RESTful API', '2024-01-15 08:00:00', NULL, '2024-02-14 23:59:00', 'Backend Developer', 'in_progress'),
(2, 'Tích hợp Chat Bot', 'Tư vấn sức khỏe tự động', '2023-08-10 10:00:00', '2023-08-25 17:30:00', '2023-08-28 17:00:00', 'AI Developer', 'completed'),
(3, 'Nhập dữ liệu khách hàng', 'Import KH cũ vào CRM', '2024-04-20 08:00:00', NULL, '2024-04-26 23:59:00', 'Data Entry', 'pending'),
(4, 'Thiết kế banner chiến dịch', 'Banner cho trang chủ TMĐT', '2023-10-25 14:00:00', '2023-10-29 18:00:00', '2023-10-30 09:00:00', 'Designer', 'completed');

TRUNCATE TABLE connect_events;
INSERT INTO connect_events (event_name, employees_id, event_date, notes, event_format, location, expertise) VALUES
('Tech Jobs Networking', 1, '2024-05-20', 'Sự kiện giao lưu tuyển dụng CNTT', 'offline', 'Trung tâm Hội nghị Q1', 'IT'),
('Wellness Talk', 2, '2024-06-10', 'Meetup chia sẻ kiến thức sức khỏe', 'offline', 'HealthPlus Hall', 'Y tế'),
('Logistics Sharing', 3, '2024-07-15', 'Chia sẻ kinh nghiệm logistics', 'offline', 'An Binh Tower', 'Logistics');

TRUNCATE TABLE event_registrations;
INSERT INTO event_registrations (applicant_id, event_id, session_starttime, session_endtime, reason_for_joining) VALUES
(1, 1, '2024-05-20 09:00:00', '2024-05-20 15:00:00', 'Mong kết nối với công ty IT'),
(5, 1, '2024-05-20 10:00:00', '2024-05-20 15:30:00', 'Mở rộng mối quan hệ ngành CNTT'),
(2, 2, '2024-06-10 08:15:00', '2024-06-10 11:30:00', 'Học hỏi về sức khỏe'),
(7, 3, '2024-07-15 13:00:00', '2024-07-15 16:00:00', 'Tham khảo kinh nghiệm logistics');

TRUNCATE TABLE posts;
INSERT INTO posts (company_id, applicant_id, content, post_type, views_count) VALUES
(NULL, 1, 'Tôi vừa hoàn thành project backend cho VietTech!', 'personal_update', 15),
(1, NULL, 'Chào mừng tham gia chương trình tuyển dụng mới!', 'job_announcement', 210),
(2, NULL, 'HealthPlus lọt TOP 5 ứng dụng sức khỏe Việt Nam', 'company_news', 77),
(NULL, 5, 'Sắp tới mình sẽ thử sức ở vị trí Content Leader', 'personal_update', 24);

TRUNCATE TABLE meetings;
INSERT INTO meetings (project_id, meeting_title, meeting_time, meeting_file, meeting_location, meeting_url, scheduled_time, meeting_description, notes) VALUES
(1, 'Kickoff dự án hệ tuyển dụng', '2024-01-12 09:00:00', NULL, 'VietTech office', NULL, '2024-01-11 12:00:00', 'Họp khởi động dự án tuyển dụng thông minh', NULL),
(2, 'Demo Chatbot HealthPlus', '2023-08-17 14:00:00', NULL, 'HealthPlus HQ', NULL, '2023-08-15 10:00:00', 'Demo chatbot cho khách hàng', 'Khách hàng phản hồi tích cực');

TRUNCATE TABLE meeting_attendees;
INSERT INTO meeting_attendees (meeting_id, applicant_id, status) VALUES
(1, 1, 'attended'), (1, 2, 'attended'), (1, 4, 'absent'), (2, 3, 'invited'), (2, 5, 'confirmed');

TRUNCATE TABLE evaluations;
INSERT INTO evaluations (task_id, applicant_id, evaluation_content, reviewer, category) VALUES
(1, 1, 'Hoàn thành API đúng deadline, code clean', 6, 'Technical'),
(2, 2, 'Đóng góp ý tưởng chatbot, phản hồi nhanh', 2, 'Innovation'),
(3, 4, 'Nhập dữ liệu chính xác nhưng hơi chậm', 1, 'Work Quality');

TRUNCATE TABLE talents;
INSERT INTO talents (applicant_id, nickname, total_projects, rating) VALUES
(1, 'Nam Developer', 4, 4.6), (5, 'Q.Anh', 3, 4.2), (3, 'Tuan Designer', 2, 4.0);

TRUNCATE TABLE tags;
INSERT INTO tags (tag_name, tag_description) VALUES
('Backend', 'Lập trình backend'), ('Data', 'Phân tích dữ liệu, trực quan hóa'),
('Testing', 'Thử nghiệm phần mềm chất lượng'), ('Content', 'Sản xuất nội dung'), ('AI', 'Trí tuệ nhân tạo, học máy');

TRUNCATE TABLE notifications;
INSERT INTO notifications (applicant_id, title, message, notification_type, action_url, priority) VALUES
(1, 'Chúc mừng bạn!', 'Bạn đã được mời phỏng vấn Backend Developer tại VietTech.', 'job_alert', 'https://viettech.com/jobs/1', 'high'),
(5, 'Tin mới', 'HealthPlus đăng việc mới Data Analyst', 'job_alert', 'https://healthplus.vn/jobs/2', 'medium'),
(3, 'Hệ thống', 'Hệ thống sẽ nâng cấp ngày 1/9.', 'system_notification', NULL, 'low');

TRUNCATE TABLE task_skills;
INSERT INTO task_skills (task_id, skill_name, required_level) VALUES
(1, 'Java', 'advanced'), (2, 'Data Analysis', 'advanced');

ALTER TABLE applicant_profiles
ADD COLUMN experience_description TEXT,
ADD COLUMN education_details TEXT,
ADD COLUMN skills TEXT,
ADD COLUMN projects TEXT;

ALTER TABLE companies MODIFY COLUMN industry VARCHAR(1000);
-- Câu lệnh ALTER TABLE employer_accounts ADD COLUMN company_id INT AFTER employer_id;
-- không thể thực hiện vì bảng `employer_accounts` không tồn tại trong lược đồ bạn cung cấp,
-- và có vẻ như bạn đã sử dụng `company_accounts` thay thế.
-- Vì `company_id` đã là khóa ngoại trong `company_accounts`, câu lệnh này không cần thiết.

SET FOREIGN_KEY_CHECKS = 1;