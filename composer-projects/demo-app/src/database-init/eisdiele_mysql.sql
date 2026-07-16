-- Create the database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS eisdiele;
USE eisdiele;

-- Create the "behaelter" table
CREATE TABLE IF NOT EXISTS behaelter (
    name VARCHAR(255) PRIMARY KEY,
    preis INT NOT NULL
);

-- Insert data into the "behaelter" table
INSERT INTO behaelter (name, preis)
VALUES
    ('Waffel', 50),
    ('Premiumwaffel', 70),
    ('Becher', 10);

-- Create the "sorte" table
CREATE TABLE IF NOT EXISTS sorte (
    name VARCHAR(255) PRIMARY KEY,
    preis INT NOT NULL
);

-- Insert data into the "sorte" table
INSERT INTO sorte (name, preis)
VALUES
    ('Cookie Dough', 110),
    ('Schokolade', 100),
    ('Vanille', 90);
