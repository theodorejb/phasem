CREATE DATABASE phasem
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

GRANT ALL PRIVILEGES ON phasem.* TO 'phasem'@'localhost';

USE phasem;

CREATE TABLE IF NOT EXISTS accounts (
    account_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(70) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    account_created DATETIME NOT NULL,
    account_last_updated DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS user_agents (
    user_agent_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_agent VARCHAR(768) NOT NULL UNIQUE,
    user_agent_created DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS auth_tokens (
    auth_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    selector CHAR(32) NOT NULL UNIQUE,
    verifier BINARY(32) NOT NULL,
    auth_token_created DATETIME NOT NULL,
    auth_token_last_renewed DATETIME NOT NULL,
    auth_token_renew_count INT UNSIGNED NOT NULL,
    auth_token_deactivated DATETIME NULL,
    mfa_last_completed DATETIME NULL,
    user_agent_id INT UNSIGNED NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (user_agent_id) REFERENCES user_agents(user_agent_id)
);

CREATE TABLE api_endpoints (
    endpoint_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    host varchar(30) not null,
    path varchar(738) not null,
    CONSTRAINT unique_host_path UNIQUE (host, path)
);

CREATE TABLE api_requests (
    request_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    auth_id INT UNSIGNED NOT NULL,
    method varchar(7) NOT NULL,
    endpoint_id INT UNSIGNED NOT NULL,
    processing_ended DATETIME NOT NULL,
    process_time_ms INT UNSIGNED NOT NULL,
    params JSON,
    error varchar(8000),
    INDEX endpoint_date (endpoint_id, processing_ended),
    FOREIGN KEY (auth_id) REFERENCES auth_tokens(auth_id),
    FOREIGN KEY (endpoint_id) REFERENCES api_endpoints(endpoint_id)
);

CREATE TABLE IF NOT EXISTS mfa_keys (
    key_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    secret VARCHAR(255) NOT NULL, -- BINARY(94) takes half the space
    mfa_requested DATETIME NOT NULL,
    mfa_enabled DATETIME NULL,
    mfa_disabled DATETIME NULL,
    failed_attempts INT UNSIGNED NOT NULL,
    last_failed_attempt DATETIME NULL,
    backup_counter INT UNSIGNED NOT NULL,
    backups_last_generated DATETIME NOT NULL,
    backups_last_viewed DATETIME NOT NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mfa_used_backup_codes (
    key_id INT UNSIGNED NOT NULL,
    counter INT UNSIGNED NOT NULL,
    date_used DATETIME NOT NULL,
    FOREIGN KEY (key_id) REFERENCES mfa_keys(key_id) ON DELETE CASCADE,
    PRIMARY KEY unique_used_codes (key_id, counter)
);
