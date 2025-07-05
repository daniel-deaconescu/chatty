# Chatty - Real-time Chat Application

A modern, responsive chat application built with PHP, SQLite, and vanilla JavaScript. Features include real-time messaging, user status tracking, notification badges, and a clean, intuitive interface.

## Features

- 💬 **Real-time messaging** with automatic updates
- 👥 **User status tracking** (online/offline)
- 🔔 **Notification badges** for unread messages
- 📌 **Pin/unpin conversations**
- 🎨 **Modern, responsive UI**
- 📱 **Multi-tab conversations** (up to 3 simultaneously)
- 🕒 **Last message preview** with timestamps
- 🎯 **Google Meet integration**

## Prerequisites

- **XAMPP** (includes Apache + PHP + MySQL)
- **Web browser** (Chrome, Firefox, Safari, Edge)
- **Git** (for cloning the repository) - _optional_

## Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/chatty.git
cd chatty
```

### 2. Start XAMPP

1. Open XAMPP Control Panel
2. Start **Apache** service
3. (Optional) Start **MySQL** service (not required for this project)

**Note:** XAMPP includes PHP, so you can run `php` commands directly in any terminal.

### 3. Place Project in XAMPP Directory

Copy the entire `chatty` folder to your XAMPP `htdocs` directory:

- **Windows**: `C:\xampp\htdocs\chatty\`
- **macOS**: `/Applications/XAMPP/htdocs/chatty/`
- **Linux**: `/opt/lampp/htdocs/chatty/`

### 4. Set Up the Database

**⚠️ IMPORTANT: You MUST run this step to create the database and sample data!**

1. Open your terminal/command prompt
2. Navigate to the project directory:
   ```bash
   cd C:\xampp\htdocs\chatty
   ```
3. Run the database setup script:
   ```bash
   php clean_and_setup_database.php
   ```

**Expected output:**

```
Cleaning database and setting up fresh data...
✓ Database cleaned
✓ Fresh users created
✓ Conversations and messages created
✓ Database setup completed successfully!
```

**If you see any errors, make sure:**

- You're in the correct directory (`C:\xampp\htdocs\chatty`)
- XAMPP Apache is running
- XAMPP is properly installed (includes PHP)

**If you get "php is not recognized as an internal or external command":**

1. Make sure XAMPP is installed
2. Try using the full path: `C:\xampp\php\php.exe clean_and_setup_database.php`
3. Or add XAMPP's PHP to your system PATH

### 5. Access the Application

Open your web browser and navigate to:

```
http://localhost/chatty/frontend/
```

## Project Structure

```
chatty/
├── backend/                 # PHP backend files
│   ├── db.php              # Database connection
│   ├── users.php           # User management API
│   ├── conversations.php   # Conversation API
│   ├── send_messages.php   # Message sending API
│   ├── mark_read.php       # Mark messages as read
│   └── toggle_pin.php      # Pin/unpin conversations
├── database/               # SQLite database files
│   ├── chatty.db          # Main database file
│   ├── chatty.db-shm      # SQLite shared memory
│   └── chatty.db-wal      # SQLite write-ahead log
├── frontend/              # Frontend files
│   ├── index.html         # Main HTML file
│   ├── css/
│   │   └── style.css      # Stylesheets
│   └── js/
│       └── app.js         # JavaScript application
├── clean_and_setup_database.php  # Database initialization script
└── README.md              # This file
```

## Database Schema

The application uses SQLite with the following tables:

- **users**: User information and status
- **conversations**: Chat conversations
- **participants**: Conversation participants
- **messages**: Chat messages with read status

## Troubleshooting

### Common Issues

#### 1. "Database connection failed"

- Ensure XAMPP Apache is running
- Check that the `database/` folder exists and is writable
- Run the database setup script: `php clean_and_setup_database.php`

#### 2. "No users found" or empty sidebar

- The database might be empty or corrupted
- Run: `php clean_and_setup_database.php` to reset with sample data

#### 3. "Permission denied" errors

- Ensure the `database/` folder has write permissions
- On Windows, run XAMPP as Administrator if needed

#### 4. Images not loading

- Check that the avatar URLs are accessible
- The app uses placeholder images from `i.pravatar.cc`

#### 5. Messages not sending

- Check browser console for JavaScript errors
- Ensure all backend PHP files are present
- Verify Apache is running and PHP is enabled

#### 6. "php is not recognized as an internal or external command"

- **Solution 1:** Use the full path: `C:\xampp\php\php.exe clean_and_setup_database.php`
- **Solution 2:** Add XAMPP's PHP to your system PATH (permanent fix):

  1. Press `Windows + R`, type `sysdm.cpl`, press Enter
  2. Click "Environment Variables" button
  3. Under "System Variables", find and select "Path", click "Edit"
  4. Click "New" and add: `C:\xampp\php`
  5. Click "OK" on all dialogs
  6. **Restart your terminal/command prompt**
  7. Try running `php --version` to verify it works

  **Alternative method:** Right-click "This PC" → Properties → Advanced system settings → Environment Variables

- **Solution 3:** Make sure XAMPP is properly installed

#### 7. "PHP Fatal error: Uncaught error" or "SQLite3 not found"

- **Solution 1:** Enable SQLite3 extension in XAMPP:
  1. Open XAMPP Control Panel
  2. Click "Config" button next to Apache
  3. Select "php.ini"
  4. Find the line: `;extension=sqlite3`
  5. Remove the semicolon (;) so it becomes: `extension=sqlite3`
  6. Save the file and restart Apache
- **Solution 2:** Try the new setup script: `php setup_database.php`
- **Solution 3:** Check if all files are present in the project directory
- **Solution 4:** Make sure you're in the correct directory (`C:\xampp\htdocs\chatty`)
- **Solution 5:** Check PHP version: `php --version` (should be 7.0 or higher)
- **Solution 6:** Verify SQLite3 is enabled: `php -m | findstr sqlite`

#### 8. "table messages has no column named read_status"

- **Solution:** The setup scripts now automatically add the missing column. Try running:
  ```bash
  php setup_database.php
  ```
  or
  ```bash
  php clean_and_setup_database.php
  ```

### Browser Console Errors

If you see errors in the browser console:

1. **CORS errors**: This is normal for local development
2. **404 errors**: Check that all files are in the correct locations
3. **Database errors**: Run the setup script again

## Development

### Adding New Features

1. **Backend**: Add new PHP files in the `backend/` directory
2. **Frontend**: Modify `frontend/js/app.js` for JavaScript logic
3. **Styling**: Update `frontend/css/style.css` for visual changes

### Database Modifications

To modify the database schema:

1. Update the migration script in `database/migration_add_read_status.sql`
2. Run the migration: `sqlite3 database/chatty.db < database/migration_add_read_status.sql`
3. Test with: `php test_notifications.php`

## API Endpoints

- `GET /backend/users.php` - Get all users with unread counts
- `GET /backend/conversations.php?user_id=X` - Get conversation with user X
- `POST /backend/send_messages.php` - Send a new message
- `POST /backend/mark_read.php` - Mark messages as read
- `POST /backend/toggle_pin.php` - Pin/unpin conversation
  **Happy chatting!** 🎉
