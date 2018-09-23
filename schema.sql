CREATE DATABASE phasem
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

USE phasem;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_fullname VARCHAR(70) NOT NULL,
    user_email VARCHAR(255) NOT NULL UNIQUE,
    user_password VARCHAR(255) NOT NULL,
    user_created DATETIME NOT NULL,
    user_last_updated DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS user_agents (
    user_agent_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_agent VARCHAR(768) NOT NULL UNIQUE,
    user_agent_created DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS auth_tokens (
    auth_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(32) NOT NULL UNIQUE,
    verifier BINARY(32) NOT NULL,
    auth_token_created DATETIME NOT NULL,
    auth_token_last_renewed DATETIME NOT NULL,
    auth_token_renew_count INT UNSIGNED NOT NULL,
    auth_token_deactivated DATETIME NULL,
    user_agent_id INT UNSIGNED NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (user_agent_id) REFERENCES user_agents(user_agent_id)
);
