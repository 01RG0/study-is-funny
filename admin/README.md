# Admin Dashboard - Study is Funny

A comprehensive admin dashboard for managing the Study is Funny educational platform.

## Features

### 🎥 Session Management
- **Upload Multiple Videos**: Add multiple videos per session (lectures, questions, summaries, exercises, homework)
- **Video Types**: Choose specific video types (lecture, questions, summary, exercise, homework)
- **Access Control**: Set permissions for who can watch (free, premium, registered users)
- **Pricing**: Set prices for premium content
- **Scheduling**: Schedule when sessions become available
- **Tags & Categories**: Organize content with tags and difficulty levels

### 👥 Student Management
- **Student Database**: View all registered students
- **Activity Tracking**: Monitor student progress and engagement
- **Premium Management**: Handle premium subscriptions
- **Communication**: Send notifications and updates

### 📊 Analytics Dashboard
- **Usage Statistics**: Track views, engagement, and popular content
- **Revenue Analytics**: Monitor subscription and session sales
- **Student Metrics**: Completion rates, retention, and satisfaction
- **System Health**: Server status, storage usage, uptime monitoring

### ⚙️ System Settings
- **General Settings**: Site configuration, contact info, branding
- **Pricing Configuration**: Set default prices and subscription rates
- **Content Policies**: File size limits, format restrictions, moderation
- **Security Settings**: Authentication, IP whitelisting, password policies
- **Notification System**: Email/SMS alerts for various events

## File Structure

```
admin/
├── login.html              # Admin login page
├── dashboard.html          # Main dashboard
├── manage-sessions.html    # Session management
├── manage-students.html    # Student management
├── analytics.html          # Analytics dashboard
├── settings.html           # System settings
├── css/
│   └── admin.css          # Admin styles
├── js/
│   └── admin.js           # Admin functionality
└── uploads/                # Upload directories
    ├── videos/            # Video files
    └── thumbnails/        # Video thumbnails
```

## Getting Started

1. **Access Admin Panel**: Navigate to `admin/login.html`
2. **Login Credentials**: Default is `admin` / `admin123`
3. **Upload Sessions**: Use the "Upload Session" section to add new content
4. **Manage Content**: Edit, delete, or modify existing sessions
5. **Monitor Analytics**: Track platform performance and user engagement
6. **Configure Settings**: Adjust pricing, security, and system preferences

## Session Upload Features

### Basic Information
- **Session Title**: Descriptive title for the session
- **Subject**: Physics, Mathematics, Statistics
- **Grade Level**: Senior 1, Senior 2
- **Teacher**: Content creator/teacher
- **Description**: Detailed session description

### Video Content
- **Multiple Videos**: Upload up to multiple videos per session
- **Video Types**: Lecture, Questions, Summary, Exercise, Homework
- **Thumbnails**: Custom thumbnail for each video
- **Duration**: Estimated video length
- **Descriptions**: Individual video descriptions

### Access Control
- **Access Type**: Free, Premium, Subscription required
- **Price**: Custom pricing per session
- **Student Types**: All, registered only, premium members
- **Schedule**: Publish date and expiry options

### Additional Resources
- **PDF Materials**: Worksheets, notes, additional resources
- **Tags**: Searchable keywords and categories
- **Difficulty**: Beginner, Intermediate, Advanced levels

## Security Features

- **Session-based Authentication**: Secure admin login
- **IP Whitelisting**: Restrict access to specific IP addresses
- **Password Policies**: Enforced strong password requirements
- **Two-Factor Authentication**: Optional 2FA for enhanced security
- **Session Timeouts**: Automatic logout after inactivity

## Analytics Features

- **Real-time Metrics**: Live dashboard with key performance indicators
- **Content Performance**: Track which sessions perform best
- **Student Engagement**: Monitor completion rates and retention
- **Revenue Tracking**: Subscription and purchase analytics
- **System Monitoring**: Server health and resource usage

## API Integration

The admin dashboard is designed to work with a backend API (MongoDB Atlas Data API in this case). Key endpoints would include:

- `POST /admin/login` - Admin authentication
- `POST /sessions` - Upload new sessions
- `GET /sessions` - Retrieve session data
- `PUT /sessions/{id}` - Update sessions
- `DELETE /sessions/{id}` - Delete sessions
- `GET /students` - Student management
- `GET /analytics` - Analytics data
- `POST /settings` - Update system settings

## Browser Support

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

## Security Considerations

1. **Change Default Password**: Immediately change the default admin password
2. **HTTPS Only**: Always access admin panel over HTTPS
3. **Regular Backups**: Implement automated database backups
4. **Monitor Logs**: Regularly review access logs for suspicious activity
5. **IP Restrictions**: Use IP whitelisting for sensitive environments

## Future Enhancements

- Bulk session uploads
- Advanced analytics with charts and graphs
- Student messaging system
- Automated content moderation
- Mobile admin app
- API rate limiting
- Advanced user roles and permissions