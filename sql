-- Create the user and set the password
CREATE USER 'kasutaja'@'localhost' IDENTIFIED BY 'Gd7HhvSX7HUEBCEkjFDy';

-- Grant all privileges to the new user
GRANT ALL PRIVILEGES ON *.* TO 'kasutaja'@'localhost' WITH GRANT OPTION;

-- Create the database
CREATE DATABASE IF NOT EXISTS kasutajad;

-- Switch to the created database
USE kasutajad;

-- Create the visitors table if it doesn't already exist
CREATE TABLE IF NOT EXISTS visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NULL,
    is_returning TINYINT(1) NOT NULL,
    visit_time DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add the emotion_state column
ALTER TABLE visitors ADD COLUMN emotion_state VARCHAR(20) NULL;

-- Modify the emotion_state column to be an ENUM with specified values
ALTER TABLE visitors MODIFY COLUMN emotion_state ENUM('good', 'okay', 'bad', 'very_bad') NULL;
