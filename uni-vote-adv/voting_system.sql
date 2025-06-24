-- Combined University Voting System Database
-- This script creates a comprehensive voting system database

-- Create the voting system database
CREATE DATABASE IF NOT EXISTS voting_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE voting_system;

-- Create voters table (enhanced version)
CREATE TABLE voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    program VARCHAR(100) NOT NULL,
    year VARCHAR(20) NOT NULL,
    faculty VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
    date_of_birth DATE,
    nationality VARCHAR(50),
    address TEXT,
    password VARCHAR(255) DEFAULT NULL, -- Will be set to student_id initially
    status ENUM('active', 'pending', 'inactive') DEFAULT 'pending',
    profile_image VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for better performance
    INDEX idx_student_id (student_id),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_faculty (faculty),
    INDEX idx_year (year)
);

-- Create admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'super_admin', 'moderator') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Create elections table (enhanced)
CREATE TABLE elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    election_type ENUM('general', 'faculty', 'departmental', 'class') DEFAULT 'general',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    eligible_years JSON, -- Store array of eligible years
    eligible_faculties JSON, -- Store array of eligible faculties
    max_votes_per_voter INT DEFAULT 1,
    allow_multiple_candidates BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES admin_users(id),
    INDEX idx_status (status),
    INDEX idx_election_type (election_type),
    INDEX idx_dates (start_date, end_date)
);

-- Create candidates table
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    voter_id INT NULL, -- Link to voters table if candidate is a student
    full_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(50) NULL,
    email VARCHAR(100) NULL,
    program VARCHAR(100) NULL,
    year VARCHAR(20) NULL,
    faculty VARCHAR(100) NULL,
    position VARCHAR(100) NULL, -- President, Vice President, etc.
    manifesto TEXT NULL,
    profile_image VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'disqualified') DEFAULT 'active',
    vote_count INT DEFAULT 0, -- Cache for quick results
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE SET NULL,
    INDEX idx_election (election_id),
    INDEX idx_status (status),
    INDEX idx_position (position)
);

-- Create positions table (for election positions)
CREATE TABLE positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NULL,
    max_candidates INT DEFAULT 10,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    INDEX idx_election (election_id),
    INDEX idx_order (display_order)
);

-- Create votes table to track voting activity (enhanced)
CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id INT NOT NULL,
    election_id INT NOT NULL,
    candidate_id INT NOT NULL,
    vote_hash VARCHAR(255) NULL, -- For anonymity and verification
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    
    -- Ensure one vote per voter per election
    UNIQUE KEY unique_vote (voter_id, election_id),
    INDEX idx_voter_election (voter_id, election_id),
    INDEX idx_election (election_id),
    INDEX idx_candidate (candidate_id),
    INDEX idx_voted_at (voted_at)
);

-- Create audit logs table for tracking changes
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'voter') NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL, -- voters, elections, candidates, etc.
    entity_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_type, user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

-- Insert sample admin user (password: admin123)
INSERT INTO admin_users (username, email, fullname, password, role, status) VALUES 
('admin', 'admin@university.edu', 'Dr. Sarah Johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active'),
('moderator', 'moderator@university.edu', 'John Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample voters data (enhanced)
INSERT INTO voters (student_id, full_name, email, phone, program, year, faculty, gender, date_of_birth, nationality, address, password, status, profile_image) VALUES
('ST2024001', 'John Smith', 'john.smith@university.edu', '+255 123 456 789', 'Computer Science', '3rd Year', 'Science & Technology', 'Male', '2001-03-15', 'Tanzanian', 'Arusha, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face'),

('ST2024002', 'Sarah Johnson', 'sarah.johnson@university.edu', '+255 987 654 321', 'Business Administration', '4th Year', 'Business & Economics', 'Female', '2000-07-22', 'Tanzanian', 'Dar es Salaam, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1494790108755-2616b169b9c0?w=100&h=100&fit=crop&crop=face'),

('ST2024003', 'Michael Chen', 'michael.chen@university.edu', '+255 555 123 456', 'Engineering', '2nd Year', 'Engineering', 'Male', '2002-11-08', 'Chinese', 'Mwanza, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'),

('ST2024004', 'Emily Rodriguez', 'emily.rodriguez@university.edu', '+255 777 888 999', 'Communications', '1st Year', 'Arts & Humanities', 'Female', '2003-04-12', 'Mexican', 'Dodoma, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pending', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&crop=face'),

('ST2024005', 'David Wilson', 'david.wilson@university.edu', '+255 444 555 666', 'Medicine', '5th Year', 'Health Sciences', 'Male', '1999-12-03', 'British', 'Mbeya, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inactive', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop&crop=face'),

('ST2024006', 'Lisa Park', 'lisa.park@university.edu', '+255 333 222 111', 'Arts & Culture', '3rd Year', 'Arts & Humanities', 'Female', '2001-09-18', 'Korean', 'Iringa, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&h=100&fit=crop&crop=face'),

('ST2024007', 'Ahmed Hassan', 'ahmed.hassan@university.edu', '+255 111 222 333', 'Mathematics', '2nd Year', 'Science & Technology', 'Male', '2002-06-14', 'Tanzanian', 'Zanzibar, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face'),

('ST2024008', 'Grace Mwangi', 'grace.mwangi@university.edu', '+255 666 777 888', 'Law', '4th Year', 'Law & Governance', 'Female', '2000-01-28', 'Kenyan', 'Morogoro, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pending', 'https://images.unsplash.com/photo-1494790108755-2616b169b9c0?w=100&h=100&fit=crop&crop=face'),

('ST2024009', 'Robert Kim', 'robert.kim@university.edu', '+255 888 999 000', 'Information Technology', '4th Year', 'Science & Technology', 'Male', '2000-05-10', 'Korean', 'Arusha, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'),

('ST2024010', 'Maria Santos', 'maria.santos@university.edu', '+255 222 333 444', 'Psychology', '2nd Year', 'Arts & Humanities', 'Female', '2002-08-20', 'Brazilian', 'Dodoma, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&crop=face'),

-- Additional voters for more comprehensive testing
('ST2024011', 'James Wilson', 'james.wilson@university.edu', '+255 111 111 111', 'Computer Science', '1st Year', 'Science & Technology', 'Male', '2003-01-10', 'Tanzanian', 'Arusha, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face'),

('ST2024012', 'Anna Thompson', 'anna.thompson@university.edu', '+255 222 222 222', 'Business Administration', '3rd Year', 'Business & Economics', 'Female', '2001-05-15', 'American', 'Dar es Salaam, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pending', 'https://images.unsplash.com/photo-1494790108755-2616b169b9c0?w=100&h=100&fit=crop&crop=face'),

('ST2024013', 'Carlos Rodriguez', 'carlos.rodriguez@university.edu', '+255 333 333 333', 'Engineering', '5th Year', 'Engineering', 'Male', '1999-09-20', 'Spanish', 'Mwanza, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inactive', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'),

('ST2024014', 'Fatima Al-Zahra', 'fatima.alzahra@university.edu', '+255 444 444 444', 'Medicine', '3rd Year', 'Health Sciences', 'Female', '2001-12-05', 'Sudanese', 'Dodoma, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&crop=face'),

('ST2024015', 'Peter Ochieng', 'peter.ochieng@university.edu', '+255 555 555 555', 'Law', '2nd Year', 'Law & Governance', 'Male', '2002-03-30', 'Kenyan', 'Mbeya, Tanzania', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face');

-- Create sample elections (enhanced)
INSERT INTO elections (title, description, election_type, start_date, end_date, status, eligible_years, eligible_faculties, created_by) VALUES
('Student Union President Election 2025', 'Annual election for Student Government President - Lead the entire student body', 'general', '2025-06-15 08:00:00', '2025-06-17 18:00:00', 'active', '["1st Year", "2nd Year", "3rd Year", "4th Year", "5th Year"]', '["Science & Technology", "Business & Economics", "Engineering", "Health Sciences", "Arts & Humanities", "Law & Governance"]', 1),

('Faculty Representative Election - Science & Technology', 'Election for Science & Technology Faculty Representative', 'faculty', '2025-06-20 09:00:00', '2025-06-22 17:00:00', 'draft', '["1st Year", "2nd Year", "3rd Year", "4th Year"]', '["Science & Technology"]', 1),

('Student Council Elections 2025', 'Elections for various Student Council positions', 'general', '2025-07-01 08:00:00', '2025-07-03 20:00:00', 'draft', '["2nd Year", "3rd Year", "4th Year", "5th Year"]', '["Science & Technology", "Business & Economics", "Engineering", "Health Sciences", "Arts & Humanities", "Law & Governance"]', 1);

-- Insert sample positions for elections
INSERT INTO positions (election_id, title, description, max_candidates, display_order) VALUES 
-- For Student Union President Election
(1, 'President', 'Student Union President - Lead the entire student body', 5, 1),

-- For Faculty Representative Election
(2, 'Faculty Representative', 'Science & Technology Faculty Representative', 3, 1),

-- For Student Council Elections
(3, 'Vice President', 'Student Union Vice President', 4, 1),
(3, 'Secretary', 'Student Union Secretary', 4, 2),
(3, 'Treasurer', 'Student Union Treasurer', 4, 3),
(3, 'Social Events Coordinator', 'Organize social events and activities', 3, 4),
(3, 'Academic Affairs Representative', 'Represent students in academic matters', 3, 5);

-- Insert sample candidates
INSERT INTO candidates (election_id, voter_id, full_name, student_id, email, position, manifesto, status) VALUES 
-- Candidates for Student Union President
(1, 1, 'John Smith', 'ST2024001', 'john.smith@university.edu', 'President', 'I will work to improve student facilities, create more opportunities for student engagement, and ensure every student voice is heard. My focus will be on enhancing campus life, improving academic support services, and building stronger connections between students and faculty.', 'active'),

(1, 3, 'Michael Chen', 'ST2024003', 'michael.chen@university.edu', 'President', 'My focus will be on academic excellence, building stronger industry partnerships, and preparing students for successful careers. I will work to bring more internship opportunities, improve laboratory facilities, and create mentorship programs.', 'active'),

(1, 6, 'Lisa Park', 'ST2024006', 'lisa.park@university.edu', 'President', 'I aim to promote diversity, inclusion, and cultural exchange on campus. My platform focuses on creating safe spaces for all students, improving mental health support services, and organizing cultural festivals that celebrate our diverse community.', 'active'),

-- Candidates for Faculty Representative - Science & Technology
(2, 7, 'Ahmed Hassan', 'ST2024007', 'ahmed.hassan@university.edu', 'Faculty Representative', 'As a Science & Technology student, I understand the unique challenges we face. I will advocate for better lab equipment, more research opportunities, and stronger connections with tech industry leaders.', 'active'),

(2, 9, 'Robert Kim', 'ST2024009', 'robert.kim@university.edu', 'Faculty Representative', 'With my background in IT, I will focus on improving our technology infrastructure, creating coding bootcamps, and establishing partnerships with tech companies for job placements.', 'active'),

-- Candidates for Student Council positions
(3, 2, 'Sarah Johnson', 'ST2024002', 'sarah.johnson@university.edu', 'Vice President', 'I aim to enhance communication between students and administration, improve student services, and ensure transparency in student government operations.', 'active'),

(3, 11, 'James Wilson', 'ST2024011', 'james.wilson@university.edu', 'Secretary', 'I will ensure proper documentation of all student activities, maintain transparency in decision-making, and create efficient communication channels for student feedback.', 'active'),

(3, 15, 'Peter Ochieng', 'ST2024015', 'peter.ochieng@university.edu', 'Treasurer', 'With my background in law and attention to detail, I will manage student funds responsibly, promote financial literacy, and ensure all expenditures are properly documented and beneficial to students.', 'active'),

(3, 14, 'Fatima Al-Zahra', 'ST2024014', 'fatima.alzahra@university.edu', 'Social Events Coordinator', 'I will organize engaging social events that bring our diverse community together, promote cultural exchange, and create memorable experiences for all students.', 'active'),

(3, 10, 'Maria Santos', 'ST2024010', 'maria.santos@university.edu', 'Academic Affairs Representative', 'As a psychology student, I understand the importance of academic support. I will advocate for better study resources, mental health services, and academic counseling programs.', 'active');

-- Insert some sample votes for statistics and testing
INSERT INTO votes (voter_id, election_id, candidate_id, vote_hash, ip_address) VALUES
-- Votes for Student Union President Election
(4, 1, 1, MD5(CONCAT(4, 1, 1, NOW())), '127.0.0.1'),
(5, 1, 1, MD5(CONCAT(5, 1, 1, NOW())), '127.0.0.1'),
(8, 1, 3, MD5(CONCAT(8, 1, 3, NOW())), '127.0.0.1'),
(10, 1, 1, MD5(CONCAT(10, 1, 1, NOW())), '127.0.0.1'),
(12, 1, 2, MD5(CONCAT(12, 1, 2, NOW())), '127.0.0.1'),
(14, 1, 3, MD5(CONCAT(14, 1, 3, NOW())), '127.0.0.1'),
(15, 1, 1, MD5(CONCAT(15, 1, 1, NOW())), '127.0.0.1');

-- Update candidate vote counts based on actual votes
UPDATE candidates SET vote_count = (
    SELECT COUNT(*) FROM votes WHERE candidate_id = candidates.id
);

-- Create useful views for reporting and statistics
CREATE VIEW voter_statistics AS
SELECT 
    faculty,
    year,
    COUNT(*) as total_voters,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_voters,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_voters,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_voters,
    ROUND(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as active_percentage
FROM voters 
GROUP BY faculty, year
ORDER BY faculty, year;

CREATE VIEW election_results AS
SELECT 
    e.id as election_id,
    e.title as election_title,
    e.status as election_status,
    c.position,
    c.full_name as candidate_name,
    c.student_id,
    c.vote_count,
    COUNT(v.id) as actual_votes,
    RANK() OVER (PARTITION BY e.id, c.position ORDER BY c.vote_count DESC) as ranking,
    ROUND(c.vote_count * 100.0 / NULLIF((SELECT COUNT(*) FROM votes WHERE election_id = e.id), 0), 2) as vote_percentage
FROM elections e
JOIN candidates c ON e.id = c.election_id
LEFT JOIN votes v ON c.id = v.candidate_id
WHERE c.status = 'active'
GROUP BY e.id, c.id
ORDER BY e.id, c.position, c.vote_count DESC;

CREATE VIEW voter_participation AS
SELECT 
    v.id,
    v.student_id,
    v.full_name,
    v.faculty,
    v.year,
    v.status,
    COUNT(vt.id) as total_votes_cast,
    GROUP_CONCAT(DISTINCT e.title ORDER BY e.start_date DESC SEPARATOR '; ') as elections_participated,
    v.last_login,
    DATEDIFF(NOW(), v.last_login) as days_since_last_login
FROM voters v
LEFT JOIN votes vt ON v.id = vt.voter_id
LEFT JOIN elections e ON vt.election_id = e.id
GROUP BY v.id
ORDER BY total_votes_cast DESC, v.full_name;

-- Create some additional indexes for better performance
CREATE INDEX idx_votes_election_candidate ON votes(election_id, candidate_id);
CREATE INDEX idx_candidates_election_position ON candidates(election_id, position);
CREATE INDEX idx_elections_status_dates ON elections(status, start_date, end_date);
CREATE INDEX idx_voters_faculty_year_status ON voters(faculty, year, status);

-- Insert some sample audit log entries
INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, new_values, ip_address) VALUES
('admin', 1, 'CREATE', 'voter', 1, '{"student_id": "ST2024001", "status": "active"}', '127.0.0.1'),
('admin', 1, 'CREATE', 'election', 1, '{"title": "Student Union President Election 2025", "status": "active"}', '127.0.0.1'),
('voter', 4, 'VOTE', 'vote', 1, '{"election_id": 1, "candidate_id": 1}', '127.0.0.1'),
('admin', 1, 'UPDATE', 'voter', 4, '{"status": "active"}', '127.0.0.1');

-- Show final database structure and summary
SELECT 'Database Setup Complete!' as Status;

SELECT 
    'VOTERS' as Category,
    COUNT(*) as Total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as Active,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as Pending,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as Inactive
FROM voters

UNION ALL

SELECT 
    'ELECTIONS' as Category,
    COUNT(*) as Total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as Active,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as Draft,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as Completed

FROM elections

UNION ALL

SELECT 
    'CANDIDATES' as Category,
    COUNT(*) as Total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as Active,
    0 as Pending,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as Inactive
FROM candidates

UNION ALL

SELECT 
    'VOTES' as Category,
    COUNT(*) as Total,
    0 as Active,
    0 as Pending,
    0 as Inactive
FROM votes;

-- Show tables created
SHOW TABLES;