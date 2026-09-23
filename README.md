# MindLink Platform — Technical & Operational Specification

MindLink is an enterprise-grade web application designed for academic institution environments to streamline team formation, project matching, and student collaboration. The platform replaces manual or random group allocation with automated skill-weighted matching, role-based access control (RBAC), and end-to-end operational moderation workflows.

---

## 1. System Architecture & Technical Specifications

The application follows an MVC-inspired architectural pattern leveraging a monolithic PHP/MySQL core integrated with responsive Bootstrap 5 components and asynchronous polling workflows.

+-----------------------------------------------------------------------+
|                            Client Layer                               |
|   HTML5 / CSS3 / JavaScript / Bootstrap 5 / Asynchronous Polling       |
+-----------------------------------+-----------------------------------+
|
v
+-----------------------------------------------------------------------+
|                       Application Layer (PHP)                         |
|  +------------------+  +-------------------+  +--------------------+  |
|  | Auth & Security  |  |  Matching Engine  |  | Messaging Gateway  |  |
|  +------------------+  +-------------------+  +--------------------+  |
|  | Project Pipeline |  | Reporting & Admin |  | Settings & Config  |  |
|  +------------------+  +-------------------+  +--------------------+  |
+-----------------------------------+-----------------------------------+
|
v
+-----------------------------------------------------------------------+
|                     Data & Persistence Layer                          |
|                       MySQL / MariaDB Engine                          |
+-----------------------------------------------------------------------+


### Stack Components
* **Backend Processing Engine**: PHP 8.x (MVC-inspired layout with CSRF tokens and prepared statements).
* **Relational Database**: MySQL 8.x (Optimized schema with explicit key dependencies for profiles, roles, messages, and reports).
* **Frontend Framework**: HTML5, CSS3, JavaScript ES6, Bootstrap 5 UI Component Library.
* **Asynchronous Transport**: Background AJAX/Polling endpoints (`check_new_projects.php`) for live content delivery without requiring full DOM reloads.

---

## 2. Core Functional Modules

### 2.1 Profile Mandatory Onboarding & RBAC
* **Enforced Completion Protocol**: New registrations trigger a blocking modal workflow (`view_profile.php`). Incomplete profiles are restricted from creating projects, submitting applications, or populating the recommendation pool.
* **Role-Based Access Control**: Users are mapped to distinct permissions (`Guest`, `Registered Student`, `Project Owner`, `Suspended Member`, `Administrator`).

### 2.2 Project Lifecycle & Application Pipeline
* **Lifecycle States**: Projects transit through three discrete statuses: `Open` -> `In Progress` -> `Completed`.
* **Role Management & Validation**: Owners establish required skill profiles, academic year constraints, and team capacity limits. Duplicate title/description checks prevent system redundancy.
* **Application Workflow**: Candidates apply with custom proposals targeted to open roles. Accepting an applicant automatically updates role fulfillment, adjusts open positions, and rejects conflicting pending applications for that role.

### 2.3 Algorithmic Recommendation Engine
* **Pre-Project Matching**: Generates compatibility scores by parsing user profile tags, skills, and interests. Technical and required project skills are weighted higher than secondary hobby/interest tags.
* **Post-Project Matching**: Utilizes completed project relational tables to suggest peer reconnection and cross-project continuity.

### 2.4 Internal Communications Gateway
* **Thread Types**: Real-time direct messaging (1-on-1) and automated group project channels created upon candidate acceptance.
* **Data Privacy Policies**: Automated message sanitization blocks sensitive personally identifiable information (PII) such as personal phone numbers.
* **Timezone Standard**: Message timestamps are standardized to Irish Local Time for uniform activity auditing.

---

## 3. Platform Moderation, Appeals & Security

[ User Violation Report ] ---> [ Admin Review Panel ] ---> [ Action: Dismiss / Remove / Suspend ]
|
v
[ Restricted Account State ] <--- [ Formal Appeal Submitted ] <--- [ Account Suspended ]
|                                                               |
+--------------------> [ Admin Reinstatement ] -----------------+


### Security Measures
* **Brute-Force Rate Limiting**: Tracks authentication failures against IP + Email pairings. Triggers a 30-minute lock state after 3 consecutive invalid attempts.
* **SQL Injection Prevention**: All queries execute using PDO / MySQLi prepared statements.
* **CSRF Mitigation**: Anti-CSRF token validation guards state-changing operations across forms and API endpoints.

### Governance & Operational Moderation
* **Contextual Reporting**: Reporting is limited to verified project peers or direct message correspondents to prevent false flagging.
* **System Appeals Workflow**: Suspended users are restricted from project and messaging functions and redirected to submit a single, persistent suspension appeal. Administrators evaluate chat logs, past incidents, and written explanations within the Admin Console before confirming or revoking account suspensions.

---

## 4. Deployment & Installation Procedures

### Production Dependencies
* PHP 8.1+
* MySQL 8.0 / MariaDB 10.4+
* Apache Web Server (`mod_rewrite` enabled) or Nginx
* SSL Certificate (HTTPS required for session security)

### Directory Structure Overview
```text
├── admin/                 # Management controllers and moderation endpoints
├── config/                # Environment parameters and DB connection instances
├── css/                   # Stylesheets (messages.css, matches.css, settings.css)
├── js/                    # Client-side handlers and AJAX functions
├── uploads/               # Authenticated profile photo assets
├── apply.php              # Application submission pipeline
├── check_new_projects.php # Background polling script
├── create_projects.php    # Project publishing controller
├── edit_projects.php     # Project lifecycle editor
├── matches.php            # Matching engine UI
├── messages.php           # Real-time chat interface
├── my_projects.php        # Managed and joined projects dashboard
├── notifications.php     # Event-driven user notifications
├── projects.php           # Project discovery catalogue
├── search.php             # Database query and filter engine
└── view_profile.php       # Profile lifecycle management
Database Setup & Initialization
Clone the repository to your host environment:

Bash
git clone [https://github.com/your-organization/mindlink.git](https://github.com/your-organization/mindlink.git)
cd mindlink
Import the database schema (.sql) into your MySQL database server.

Configure environment parameters in /config/database.php:

PHP
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'mindlink_db');
