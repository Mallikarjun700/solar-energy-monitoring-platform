# Initial Database Schema Design

This document outlines the initial relational database schema for core entities in the **Solar Energy Monitoring & Asset Management Platform**.

> **Note:** This schema serves as the baseline entity-relationship design for application workflows (users, plants, assets, devices, and initial telemetry data). Further optimization and sharding/time-series strategies will be applied to the `telemetry` model to handle peak loads (100K events/sec).


[users] ───< [roles]
│
└───< [plants] ───< [assets] ───< [devices] ───< [telemetry]

---

## 1. Entity-Relationship Overview

Here is the complete initial database design document formatted cleanly as Markdown. You can save this directly into your repository at `docs/database/database-design.md`.

---

```markdown
# Initial Database Schema Design

This document outlines the initial relational database schema for core entities in the **Solar Energy Monitoring & Asset Management Platform**.

> **Note:** This schema serves as the baseline entity-relationship design for application workflows (users, plants, assets, devices, and initial telemetry data). Further optimization and sharding/time-series strategies will be applied to the `telemetry` model to handle peak loads (100K events/sec).

---


## 1. Entity-Relationship Overview


[users] ───< [roles]
│
└───< [plants] ───< [assets] ───< [devices] ───< [telemetry]


## 2. Table Definitions

### DATABASE: `solar_monitoring_dev`
CREATE DATABASE solar_monitoring_dev 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;


### Table: `plants`
Stores operational solar power plants.

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | `PRIMARY KEY`, `AUTO_INCREMENT` | Unique plant identifier |
| `name` | `VARCHAR(255)` | `NOT NULL` | Name of the solar plant |
| `code` | `VARCHAR(50)` | `NOT NULL`, `UNIQUE` | Unique identifier/code for the plant |
| `location` | `VARCHAR(255)` | `NULLABLE` | Physical location / address |
| `capacity_kw` | `DECIMAL(10, 2)` | `NOT NULL` | Installed power capacity in kW |
| `status` | `VARCHAR(20)` | `DEFAULT 'ACTIVE'` | Operational status (`ACTIVE`, `INACTIVE`, `MAINTENANCE`) |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Record update timestamp |

---

### Table: `assets`
Stores physical assets contained within a plant (e.g., Inverter Racks, Solar Arrays, Sub-stations).

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | `PRIMARY KEY`, `AUTO_INCREMENT` | Unique asset identifier |
| `plant_id` | `BIGINT` | `FOREIGN KEY` $\rightarrow$ `plants(id)` | Parent plant reference |
| `name` | `VARCHAR(255)` | `NOT NULL` | Asset name |
| `asset_type` | `VARCHAR(50)` | `NOT NULL` | Type of asset (e.g., `INVERTER`, `TRACKER`, `TRANSFORMER`) |
| `serial_number` | `VARCHAR(100)` | `UNIQUE` | Hardware serial number |
| `status` | `VARCHAR(20)` | `DEFAULT 'ACTIVE'` | Asset operational status |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Record update timestamp |

---

### Table: `devices`
Stores individual sensors or IoT edge units attached to an asset.

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | `PRIMARY KEY`, `AUTO_INCREMENT` | Unique device identifier |
| `asset_id` | `BIGINT` | `FOREIGN KEY` $\rightarrow$ `assets(id)` | Parent asset reference |
| `device_type` | `VARCHAR(50)` | `NOT NULL` | Category of device (e.g., `THERMAL_SENSOR`, `POWER_METER`) |
| `serial_number` | `VARCHAR(100)` | `UNIQUE` | Device hardware identifier |
| `status` | `VARCHAR(20)` | `DEFAULT 'ONLINE'` | Current connectivity status (`ONLINE`, `OFFLINE`, `FAULT`) |
| `last_seen_at` | `TIMESTAMP` | `NULLABLE` | Last telemetry submission timestamp |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Record update timestamp |

---

### Table: `telemetry`
Captures operational time-series metrics transmitted by devices.

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | `PRIMARY KEY`, `AUTO_INCREMENT` | Unique log entry identifier |
| `device_id` | `BIGINT` | `FOREIGN KEY` $\rightarrow$ `devices(id)` | Sensor source device reference |
| `timestamp` | `TIMESTAMP` | `NOT NULL` | Telemetry event time |
| `temperature` | `DECIMAL(5, 2)` | `NULLABLE` | Temperature reading (°C) |
| `voltage` | `DECIMAL(6, 2)` | `NULLABLE` | Electrical potential reading (V) |
| `current` | `DECIMAL(6, 2)` | `NULLABLE` | Electrical current reading (A) |
| `power` | `DECIMAL(8, 2)` | `NULLABLE` | Instantaneous output power (kW) |
| `energy_generated` | `DECIMAL(10, 2)` | `NULLABLE` | Cumulative generated energy (kWh) |
| `status` | `VARCHAR(20)` | `DEFAULT 'NORMAL'` | Data payload status indicator |

---

## 3. SQL DDL Implementation

```sql
-- Create Plants Table
CREATE TABLE plants (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    location VARCHAR(255),
    capacity_kw DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Assets Table
CREATE TABLE assets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    plant_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    asset_type VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    status VARCHAR(20) DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assets_plant FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);

-- Create Devices Table
CREATE TABLE devices (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    asset_id BIGINT NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    status VARCHAR(20) DEFAULT 'ONLINE',
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_devices_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
);

-- Create Telemetry Table
CREATE TABLE telemetry (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    temperature DECIMAL(5, 2),
    voltage DECIMAL(6, 2),
    current DECIMAL(6, 2),
    power DECIMAL(8, 2),
    energy_generated DECIMAL(10, 2),
    status VARCHAR(20) DEFAULT 'NORMAL',
    CONSTRAINT fk_telemetry_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    INDEX idx_device_timestamp (device_id, timestamp DESC)
);
