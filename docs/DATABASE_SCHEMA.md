# MongoDB Database Schema Documentation

## Database Information

**Database Name:** `attendance_system`  
**Connection URI:** `mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0`  
**Total Collections:** 25

---

## Collections Overview

| Collection Name | Document Count | Description |
|----------------|----------------|-------------|
| `users` | 62 | Platform users (admins, students, assistants) |
| `sessions` | 83 | Teaching sessions (weekly/recurring classes) |
| `all_students_view` | 30 | Comprehensive student view with session tracking |
| `active_students_view` | 30 | Active students view |
| `senior1_math` | 680 | Senior 1 Math students and their attendance |
| `senior2_pure_math` | 1,302 | Senior 2 Pure Math students and attendance |
| `senior2_physics` | 173 | Senior 2 Physics students and attendance |
| `senior2_mechanics` | 914 | Senior 2 Mechanics students and attendance |
| `senior3_math` | 84 | Senior 3 Math students and attendance |
| `senior3_physics` | 56 | Senior 3 Physics students and attendance |
| `senior3_statistics` | 50 | Senior 3 Statistics students and attendance |
| `attendances` | 461 | Individual attendance records |
| `callsessions` | 38 | Phone call sessions |
| `callsessionstudents` | 12,382 | Student participation in call sessions |
| `activitylogs` | 165 | User activity logs |
| `auditlogs` | 1,519 | System audit trail |
| `errorlogs` | 30 | Error tracking |
| `transactions` | 1 | Financial transactions |
| `centers` | 5 | Teaching centers |
| `assistants` | 0 | Teaching assistants |
| `counters` | 4 | ID counter sequences |
| `whatsappschedules` | 7 | WhatsApp message schedules |
| `deleteditems` | 4 | Soft-deleted items (recoverable) |
| `attendance` | 1 | Legacy attendance data |
| `absence` | 0 | Absence tracking |

---

## Core Collections

### 1. `users` Collection

Platform users including students, admins, and assistants.

#### Schema

```javascript
{
  _id: ObjectId,                    // MongoDB unique identifier
  name: String,                     // User's full name
  email: String,                    // Email address (optional for students)
  phone: String,                    // Phone number (primary identifier for students)
  password: String,                 // Plain text password (students) or hashed (admins)
  password_hash: String,            // Hashed password for admins
  role: String,                     // User role: "admin" | "student" | "assistant"
  grade: String,                    // Student grade: "senior1" | "senior2" | "senior3"
  subjects: Array<String>,          // Array of enrolled subjects
  assignedCenters: Array<ObjectId>, // Centers assigned to assistants
  joinDate: UTCDateTime,            // Account creation date
  lastLogin: UTCDateTime,           // Last login timestamp
  isActive: Boolean,                // Account status
  totalSessionsViewed: Number,      // Total online sessions viewed
  totalWatchTime: Number,           // Total watch time in minutes
  activityLog: Array<Object>,       // User activity history
  createdAt: UTCDateTime,           // Record creation timestamp
  updatedAt: UTCDateTime,           // Last update timestamp
  __v: Number                       // MongoDB version key
}
```

#### Example Documents

**Admin User:**
```json
{
  "_id": {"$oid": "6924bb531f73d76711936065"},
  "name": "abdallah",
  "email": "admin@example.com",
  "password_hash": "$2a$10$cK455J5TAa6IVjBePXWlzuuzAzGXvPdjgkbh4.FpKMgEwckQyBkMC",
  "role": "admin",
  "assignedCenters": [],
  "createdAt": {"$date": {"$numberLong": "1764014931255"}},
  "updatedAt": {"$date": {"$numberLong": "1765121269582"}},
  "__v": 0
}
```

**Student User:**
```json
{
  "_id": {"$oid": "6924c3e8ef58be28b5b33ec4"},
  "name": "أحمد محمد",
  "phone": "01280912031",
  "password": "123456",
  "grade": "senior1",
  "subjects": ["physics", "mathematics", "statistics"],
  "joinDate": {"$date": {"$numberLong": "1764016872000"}},
  "lastLogin": {"$date": {"$numberLong": "1764020400000"}},
  "isActive": true,
  "totalSessionsViewed": 5,
  "totalWatchTime": 120,
  "activityLog": []
}
```

---

### 2. `sessions` Collection

Teaching sessions configuration (weekly recurring classes).

#### Schema

```javascript
{
  _id: ObjectId,                  // Session unique identifier
  assistant_id: ObjectId | null,  // Assigned assistant
  center_id: ObjectId,            // Teaching center reference
  subject: String,                // Subject name (e.g., "Math s3", "Physics S2")
  start_time: UTCDateTime,        // Session start time
  recurrence_type: String,        // "weekly" | "monthly" | "once"
  day_of_week: Number,            // Day of week (0=Sunday, 6=Saturday)
  is_active: Boolean,             // Session status
  createdAt: UTCDateTime,         // Creation timestamp
  updatedAt: UTCDateTime,         // Last update timestamp
  __v: Number                     // Version key
}
```

#### Example Document

```json
{
  "_id": {"$oid": "6927a210db06b6b8ac7da06f"},
  "assistant_id": null,
  "center_id": {"$oid": "6925d421d4303bf0294bace8"},
  "subject": "Math s3",
  "start_time": {"$date": {"$numberLong": "1764239400000"}},
  "recurrence_type": "weekly",
  "day_of_week": 4,
  "is_active": true,
  "createdAt": {"$date": {"$numberLong": "1764205072883"}},
  "updatedAt": {"$date": {"$numberLong": "1764853689939"}},
  "__v": 0
}
```

---

### 3. `all_students_view` Collection

**Primary collection for student management** - Comprehensive view of students with all session data.

#### Schema

```javascript
{
  _id: ObjectId,                  // Unique identifier
  studentId: Number,              // Student ID number (unique)
  studentName: String,            // Student's full name
  phone: String,                  // Student phone number (primary contact)
  parentPhone: String,            // Parent/guardian phone number
  subject: String,                // Enrolled subject (e.g., "S3 Math", "S2 Physics")
  center: ObjectId,               // Teaching center reference
  paymentAmount: Number,          // Session payment amount in EGP
  isActive: Boolean,              // Student enrollment status
  note: String | null,            // Additional notes
  balance: Number,                // Current balance (if prepaid)
  
  // Session fields (session_1, session_2, ..., session_N)
  session_1: {
    date: String,                 // Session date (YYYY-MM-DD)
    attendanceStatus: String,     // "Present" | "Absent" | "Late" | "Excused"
    homeworkStatus: String | null,// "done" | "not done" | "not complete" | null
    examMark: Number | null,      // Exam score
    centerAttendance: ObjectId | Number, // Center where attended
    paidAmount: Number,           // Amount paid for this session
    books: Number,                // Books purchased (amount)
    comment: String | null,       // Session comment/note
    recordedBy: {                 // Who recorded each field
      attendanceAssistant: Number | String | ObjectId | null,
      homeworkAssistant: Number | String | ObjectId | null,
      examAssistant: Number | String | ObjectId | null
    } | null,
    time: String,                 // Record time (HH:MM:SS)
    source: String,               // "old" | "auto-generated" | "auto-generated-weekly" | "legacy"
    online_attendance: Boolean,   // Online attendance marked
    online_attendance_assistant: String | ObjectId | null,
    online_attendance_completed_at: String | null,
    online_session: Boolean,      // Online session access granted
    online_session_assistant: String | ObjectId | null,
    online_session_completed_at: String | null,
    isCompensation: Boolean,      // Compensation session flag
    originalCenter: ObjectId | null // Original center if compensation
  },
  // ... session_2, session_3, ... up to session_N
}
```

#### Complete Example Document (All Fields)

```json
{
  "_id": {"$oid": "6951edb322f24a89e678949b"},
  "studentId": 82,
  "studentName": "Basel zakry",
  "phone": "+201550211027",
  "parentPhone": "+201550211034",
  "subject": "S3 Math",
  "center": {"$oid": "6925d421d4303bf0294bace8"},
  "paymentAmount": 85,
  "isActive": true,
  "note": null,
  "balance": 9830,
  
  "session_1": {
    "date": "2025-10-20",
    "attendanceStatus": "Absent",
    "homeworkStatus": null,
    "examMark": null,
    "centerAttendance": {"$oid": "6925d421d4303bf0294bace8"},
    "paidAmount": 0,
    "books": 0,
    "comment": "Auto-generated absence",
    "recordedBy": null,
    "time": "00:00:00",
    "source": "auto-generated",
    "online_attendance": false,
    "online_attendance_assistant": null,
    "online_attendance_completed_at": null,
    "online_session": false,
    "online_session_assistant": null,
    "online_session_completed_at": null
  },
  
  "session_2": {
    "date": "2025-10-23",
    "attendanceStatus": "Present",
    "homeworkStatus": "done",
    "examMark": null,
    "centerAttendance": {"$oid": "6925d421d4303bf0294bace8"},
    "paidAmount": 85,
    "books": 0,
    "comment": null,
    "recordedBy": {
      "attendanceAssistant": 1,
      "homeworkAssistant": 28,
      "examAssistant": null
    },
    "time": "12:00:40",
    "source": "old",
    "online_attendance": false,
    "online_attendance_assistant": null,
    "online_attendance_completed_at": null,
    "online_session": false,
    "online_session_assistant": null,
    "online_session_completed_at": null
  },
  
  "session_3": {
    "date": "2025-10-27",
    "attendanceStatus": "Present",
    "homeworkStatus": null,
    "examMark": null,
    "centerAttendance": {"$oid": "6925d421d4303bf0294bace8"},
    "paidAmount": 85,
    "books": 0,
    "comment": null,
    "recordedBy": {
      "attendanceAssistant": 1,
      "homeworkAssistant": null,
      "examAssistant": null
    },
    "time": "13:43:47",
    "source": "old",
    "online_attendance": false,
    "online_attendance_assistant": null,
    "online_attendance_completed_at": null,
    "online_session": false,
    "online_session_assistant": null,
    "online_session_completed_at": null
  },
  
  "session_5": {
    "date": "2025-11-03",
    "attendanceStatus": "Present",
    "homeworkStatus": "done",
    "examMark": 6,
    "centerAttendance": {"$oid": "6925d421d4303bf0294bace8"},
    "paidAmount": 85,
    "books": 0,
    "comment": null,
    "recordedBy": {
      "attendanceAssistant": 1,
      "homeworkAssistant": null,
      "examAssistant": 2
    },
    "time": "16:09:54",
    "source": "old",
    "online_attendance": false,
    "online_attendance_assistant": null,
    "online_attendance_completed_at": null,
    "online_session": false,
    "online_session_assistant": null,
    "online_session_completed_at": null
  },
  
  "session_6": {
    "date": "2025-11-06",
    "attendanceStatus": "Present",
    "homeworkStatus": "done",
    "examMark": 10,
    "centerAttendance": {"$oid": "6925d421d4303bf0294bace8"},
    "paidAmount": 85,
    "books": 0,
    "comment": null,
    "recordedBy": {
      "attendanceAssistant": 1,
      "homeworkAssistant": 28,
      "examAssistant": 1
    },
    "time": "12:01:21",
    "source": "old",
    "online_attendance": false,
    "online_attendance_assistant": null,
    "online_attendance_completed_at": null,
    "online_session": false,
    "online_session_assistant": null,
    "online_session_completed_at": null
  },
  
  "session_27": {
    "date": "2026-01-17",
    "attendanceStatus": "Present",
    "homeworkStatus": null,
    "examMark": null,
    "centerAttendance": 6925,
    "paidAmount": 5000,
    "books": 0,
    "comment": "Compensation | Remaining balance: 9830 EGP",
    "recordedBy": {
      "attendanceAssistant": "6924c3e8ef58be28b5b33ec4"
    },
    "time": "16:03:41",
    "online_session": true,
    "online_session_completed_at": "2026-01-17T14:03:55.912Z",
    "online_session_assistant": "6924c3e8ef58be28b5b33ec4",
    "online_attendance": false,
    "online_attendance_completed_at": null,
    "online_attendance_assistant": null,
    "isCompensation": true,
    "originalCenter": {"$oid": "6925d421d4303bf0294bace8"}
  }
}
```

**Key Points:**
- Dynamic session fields (`session_1` through `session_N`)
- Each session tracks attendance, homework, exams, payments
- Online session access control per session
- Support for compensation sessions
- Tracks which assistant recorded each field
- Balance tracking for prepaid students

---

### 4. Subject-Specific Collections

These collections store student records per subject/grade combination.

#### Collection Names
- `senior1_math` - Senior 1 Mathematics
- `senior2_pure_math` - Senior 2 Pure Mathematics
- `senior2_physics` - Senior 2 Physics
- `senior2_mechanics` - Senior 2 Mechanics
- `senior3_math` - Senior 3 Mathematics
- `senior3_physics` - Senior 3 Physics
- `senior3_statistics` - Senior 3 Statistics

#### Schema (Same for All Subject Collections)

```javascript
{
  _id: ObjectId,
  studentId: Number,              // Unique student ID
  studentName: String,            // Student name
  phone: String,                  // Phone number with country code
  parentPhone: String,            // Parent phone
  subject: String,                // Subject name (e.g., "S1 Math")
  center: ObjectId,               // Teaching center
  paymentAmount: Number,          // Payment per session
  isActive: Boolean,              // Enrollment status
  note: String | null,            // Notes
  
  // Dynamic session fields
  session_1: { /* Same structure as all_students_view */ },
  session_2: { /* Same structure */ },
  // ... up to session_N
}
```

#### Example Document (senior1_math)

```json
{
  "_id": {"$oid": "6951edac22f24a89e678889f"},
  "studentId": 1478,
  "studentName": "Abdelrhman Mohamed Mamdouh",
  "phone": "+201123217315",
  "parentPhone": "119372264",
  "subject": "S1 Math",
  "center": {"$oid": "6925a6964d99b8fee6eeee07"},
  "paymentAmount": 75,
  "isActive": true,
  "note": null,
  
  "session_1": {
    "date": "2025-10-18",
    "attendanceStatus": "Absent",
    "homeworkStatus": null,
    "examMark": null,
    "centerAttendance": {"$oid": "6925a6964d99b8fee6eeee07"},
    "paidAmount": 0,
    "books": 0,
    "comment": "Auto-generated absence",
    "recordedBy": null,
    "time": "00:00:00",
    "source": "auto-generated"
  },
  
  "session_3": {
    "date": "2025-11-05",
    "attendanceStatus": "Present",
    "homeworkStatus": "not done",
    "examMark": null,
    "centerAttendance": {"$oid": "6925a6964d99b8fee6eeee07"},
    "paidAmount": 75,
    "books": 0,
    "comment": null,
    "recordedBy": {
      "attendanceAssistant": 1,
      "homeworkAssistant": 32,
      "examAssistant": null
    },
    "time": "17:19:31",
    "source": "old"
  },
  
  "session_4": {
    "date": "2025-11-12",
    "attendanceStatus": "Present",
    "homeworkStatus": "not complete",
    "examMark": null,
    "centerAttendance": {"$oid": "6925a6964d99b8fee6eeee07"},
    "paidAmount": 75,
    "books": 0,
    "comment": null,
    "recordedBy": {
      "attendanceAssistant": 1,
      "homeworkAssistant": 32,
      "examAssistant": null
    },
    "time": "16:39:30",
    "source": "old"
  }
}
```

---

### 5. `centers` Collection

Teaching center/location information.

#### Schema

```javascript
{
  _id: ObjectId,
  name: String,                   // Center name
  address: String,                // Physical address
  phone: String,                  // Contact phone
  capacity: Number,               // Maximum students
  isActive: Boolean,              // Operational status
  createdAt: UTCDateTime,
  updatedAt: UTCDateTime,
  __v: Number
}
```

---

### 6. `attendances` Collection

Individual attendance records (alternative tracking method).

#### Schema

```javascript
{
  _id: ObjectId,
  student_id: ObjectId,           // Student reference
  session_id: ObjectId,           // Session reference
  status: String,                 // "present" | "absent" | "late" | "excused"
  timestamp: UTCDateTime,         // Check-in time
  notes: String,                  // Additional notes
  recorded_by: ObjectId,          // Assistant who recorded
  createdAt: UTCDateTime,
  __v: Number
}
```

---

### 7. `transactions` Collection

Financial transactions and payments.

#### Schema

```javascript
{
  _id: ObjectId,
  student_id: ObjectId | Number,  // Student reference
  session_id: ObjectId,           // Related session
  amount: Number,                 // Transaction amount
  type: String,                   // "payment" | "refund" | "balance_adjustment"
  payment_method: String,         // "cash" | "card" | "bank_transfer" | "wallet"
  description: String,            // Transaction description
  balance_before: Number,         // Balance before transaction
  balance_after: Number,          // Balance after transaction
  processed_by: ObjectId,         // User who processed
  createdAt: UTCDateTime,
  updatedAt: UTCDateTime,
  __v: Number
}
```

---

### 8. `activitylogs` Collection

User activity tracking.

#### Schema

```javascript
{
  _id: ObjectId,
  user_id: ObjectId,              // User who performed action
  action: String,                 // Action type
  target_type: String,            // Type of affected resource
  target_id: ObjectId | String,   // Affected resource ID
  description: String,            // Human-readable description
  ip_address: String,             // User IP address
  user_agent: String,             // Browser/client info
  createdAt: UTCDateTime,
  __v: Number
}
```

---

### 9. `auditlogs` Collection

System audit trail for compliance and security.

#### Schema

```javascript
{
  _id: ObjectId,
  user_id: ObjectId,              // User who made change
  action: String,                 // "create" | "update" | "delete" | "view"
  resource_type: String,          // Collection/resource type
  resource_id: ObjectId | String, // Resource identifier
  changes: Object,                // Before/after values
  timestamp: UTCDateTime,
  ip_address: String,
  user_agent: String,
  createdAt: UTCDateTime,
  __v: Number
}
```

---

### 10. `errorlogs` Collection

Error tracking and debugging.

#### Schema

```javascript
{
  _id: ObjectId,
  error_type: String,             // Error classification
  message: String,                // Error message
  stack_trace: String,            // Full stack trace
  user_id: ObjectId | null,       // User context if available
  request_url: String,            // Request that caused error
  request_method: String,         // HTTP method
  request_body: Object,           // Request payload
  severity: String,               // "low" | "medium" | "high" | "critical"
  resolved: Boolean,              // Resolution status
  resolved_at: UTCDateTime | null,
  resolved_by: ObjectId | null,
  createdAt: UTCDateTime,
  __v: Number
}
```

---

### 11. `counters` Collection

Auto-increment ID generators.

#### Schema

```javascript
{
  _id: String,                    // Counter name (e.g., "studentId")
  sequence_value: Number          // Current counter value
}
```

#### Example

```json
{
  "_id": "studentId",
  "sequence_value": 1478
}
```

---

### 12. `callsessions` Collection

Phone call session tracking.

#### Schema

```javascript
{
  _id: ObjectId,
  assistant_id: ObjectId,         // Assistant making calls
  start_time: UTCDateTime,        // Call session start
  end_time: UTCDateTime | null,   // Call session end
  total_calls: Number,            // Number of calls made
  total_duration: Number,         // Total call time (minutes)
  status: String,                 // "active" | "completed" | "cancelled"
  notes: String,
  createdAt: UTCDateTime,
  updatedAt: UTCDateTime,
  __v: Number
}
```

---

### 13. `callsessionstudents` Collection

Individual call records per student.

#### Schema

```javascript
{
  _id: ObjectId,
  call_session_id: ObjectId,      // Parent call session
  student_id: Number,             // Student called
  phone_number: String,           // Phone dialed
  call_status: String,            // "answered" | "no_answer" | "busy" | "invalid"
  call_duration: Number,          // Duration in seconds
  notes: String,                  // Call notes
  timestamp: UTCDateTime,
  createdAt: UTCDateTime,
  __v: Number
}
```

---

### 14. `whatsappschedules` Collection

Scheduled WhatsApp message campaigns.

#### Schema

```javascript
{
  _id: ObjectId,
  message_template: String,       // Message content
  recipients: Array<String>,      // Phone numbers
  schedule_time: UTCDateTime,     // When to send
  status: String,                 // "pending" | "sent" | "failed" | "cancelled"
  sent_count: Number,             // Successfully sent
  failed_count: Number,           // Failed sends
  created_by: ObjectId,
  createdAt: UTCDateTime,
  updatedAt: UTCDateTime,
  __v: Number
}
```

---

### 15. `deleteditems` Collection

Soft-deleted items (recoverable).

#### Schema

```javascript
{
  _id: ObjectId,
  item_type: String,              // Original collection name
  item_id: String,                // Original document ID
  item_data: Object,              // Complete original document
  deleted_by: ObjectId,           // User who deleted
  deletion_reason: String,        // Reason for deletion
  can_restore: Boolean,           // Restoration permitted
  deleted_at: UTCDateTime,
  createdAt: UTCDateTime,
  updatedAt: UTCDateTime,
  __v: Number
}
```

---

## Data Type Reference

### MongoDB BSON Types Used

| Type | Description | Example |
|------|-------------|---------|
| `ObjectId` | 12-byte unique identifier | `{"$oid": "6924bb531f73d76711936065"}` |
| `UTCDateTime` | Timestamp (milliseconds since epoch) | `{"$date": {"$numberLong": "1764014931255"}}` |
| `String` | UTF-8 text | `"أحمد محمد"` |
| `Number` | Integer or float | `85`, `10.5` |
| `Boolean` | True/false | `true`, `false` |
| `Array` | List of values | `["math", "physics"]` |
| `Object` | Embedded document | `{"name": "value"}` |
| `null` | Null/undefined value | `null` |

### Common Enum Values

**Attendance Status:**
- `"Present"` - Student attended
- `"Absent"` - Student did not attend
- `"Late"` - Student arrived late
- `"Excused"` - Excused absence

**Homework Status:**
- `"done"` - Completed fully
- `"not done"` - Not completed
- `"not complete"` - Partially completed
- `null` - Not assigned

**User Roles:**
- `"admin"` - System administrator
- `"student"` - Student account
- `"assistant"` - Teaching assistant

**Grades:**
- `"senior1"` - 1st year secondary
- `"senior2"` - 2nd year secondary
- `"senior3"` - 3rd year secondary

**Subjects:**
- `"mathematics"` - Math
- `"physics"` - Physics
- `"mechanics"` - Mechanics
- `"statistics"` - Statistics

---

## Indexes and Performance

### Recommended Indexes

```javascript
// users collection
db.users.createIndex({ phone: 1 }, { unique: true, sparse: true })
db.users.createIndex({ email: 1 }, { unique: true, sparse: true })
db.users.createIndex({ role: 1, isActive: 1 })

// all_students_view collection
db.all_students_view.createIndex({ studentId: 1 }, { unique: true })
db.all_students_view.createIndex({ phone: 1 })
db.all_students_view.createIndex({ subject: 1, isActive: 1 })

// sessions collection
db.sessions.createIndex({ center_id: 1, subject: 1, is_active: 1 })
db.sessions.createIndex({ day_of_week: 1, start_time: 1 })

// attendances collection
db.attendances.createIndex({ student_id: 1, session_id: 1 })
db.attendances.createIndex({ timestamp: -1 })
```

---

## Query Examples

### Get Student with All Session Data

```javascript
db.all_students_view.findOne(
  { phone: "+201550211027" },
  { _id: 1, studentName: 1, phone: 1, subject: 1, isActive: 1, 
    session_1: 1, session_2: 1, session_3: 1 }
)
```

### Find Active Students in Subject

```javascript
db.senior2_pure_math.find(
  { isActive: true },
  { studentName: 1, phone: 1, paymentAmount: 1 }
).sort({ studentName: 1 })
```

### Check Online Session Access

```javascript
db.all_students_view.findOne({
  studentId: 82,
  "session_5.online_session": true
})
```

### Get Attendance Statistics

```javascript
db.all_students_view.aggregate([
  { $match: { subject: "S3 Math", isActive: true } },
  { $project: {
      studentName: 1,
      presentSessions: {
        $size: {
          $filter: {
            input: { $objectToArray: "$$ROOT" },
            cond: { $eq: ["$$this.v.attendanceStatus", "Present"] }
          }
        }
      }
    }
  }
])
```

---

## Notes

1. **Phone Number Format**: Use international format with `+` prefix (e.g., `+201234567890`)
2. **Session Numbering**: Sessions are numbered sequentially starting from 1
3. **Soft Deletes**: Items are moved to `deleteditems` collection instead of permanent deletion
4. **Date Format**: Dates in session objects use `YYYY-MM-DD` format
5. **Time Format**: Times use `HH:MM:SS` 24-hour format
6. **Currency**: All payment amounts are in Egyptian Pounds (EGP)
7. **Subject Codes**: Use format like "S1 Math", "S2 Physics", "S3 Statistics"

---

**Document Version:** 1.0  
**Last Updated:** January 20, 2026  
**Generated from:** MongoDB Atlas Cluster (attendance_system database)
