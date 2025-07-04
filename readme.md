# Chatty - Live Chat Application

A real-time chat application with multiple conversation windows, user status tracking and message history.

## Features

- Multiple concurrent chat windows (up to 3)
- User status indicators (online/offline)
- Pinned conversations
- Message history

## Prerequisites

- [XAMPP](https://www.apachefriends.org/download.html) (or similar PHP server stack)
- Web browser (Chrome, Firefox, Edge recommended)
- SQLite extension (usually included with XAMPP)

## Installation Guide

### 1. Set Up XAMPP

1. Download and install XAMPP from [Apache Friends](https://www.apachefriends.org/download.html)
2. Launch the XAMPP Control Panel
3. Start the `Apache` module

### 2. Project Setup

1. Clone or download this repository
2. Place the project folder in your XAMPP `htdocs` directory:
   C:\xampp\htdocs\chatty (Windows)
   /Applications/XAMPP/htdocs/chatty (Mac)
   /opt/lampp/htdocs/chatty (Linux)

text

### 3. Database Setup

The application includes a pre-configured SQLite database file:
/chatty/database/chatty.db

text

No additional setup is required as the database is already included in the project.

## Running the Application

1. Ensure XAMPP's Apache server is running
2. Open your web browser and navigate to:
   http://localhost/chatty/frontend/

text 3. The application should load with sample data

## Project Structure

<pre>
chatty/
├── backend/
│   ├── conversations.php
│   ├── db.php
│   ├── send_messages.php
│   ├── toggle_pin.php
│   └── users.php
├── database/
│   └── chatty.db
└── frontend/
    ├── css/
    │   └── style.css
    ├── js/
    │   └── app.js
    └── index.html
</pre>
