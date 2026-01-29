# Study is Funny Platform

A comprehensive student management and educational platform with features for attendance tracking, homework management, and video lessons.

## 🚀 Project Structure

The project has been organized for better maintainability and clarity:

### Core Directories
- `admin/`: Admin panel for managing students, sessions, and payments.
- `api/`: Backend PHP API endpoints.
- `auth/`: Authentication logic (login/register).
- `classes/`: Object-oriented PHP business logic and database models.
- `config/`: Configuration files and environment-specific variants.
- `docs/`: Comprehensive project documentation, implementation guides, and plans.
- `student/`: Student-specific portal and dashboards.

### Assets & Resources
- `css/` & `js/`: Shared styles and frontend logic.
- `images/` & `webfonts/`: Static assets.
- `uploads/`: Dynamic storage for videos, homework, and session resources.

### Support & Maintenance
- `archive/`: Deprecated or old files kept for reference.
- `debug/`: Utility scripts for database diagnosis and API testing.
- `logs/`: Application error and access logs.

### Grade-Specific Content
- `senior1/`, `senior2/`, `senior3/`: Landing pages and subject-specific content for different grade levels.

## 🛠️ Getting Started

1.  Ensure you have PHP and a MongoDB instance (or Atlas) configured.
2.  Update `config/config.php` with your connection strings.
3.  Run the local server using `run.ps1` or `php -S localhost:8000 router.php`.

For more detailed information, please refer to the documents in the `docs/` folder.
