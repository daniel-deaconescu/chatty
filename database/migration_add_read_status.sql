-- Migration to add read_status column to messages table
-- This will track whether messages have been read by recipients

-- Add read_status column to messages table
ALTER TABLE messages ADD COLUMN read_status INTEGER DEFAULT 0;

-- Create index for better performance when querying unread messages
CREATE INDEX IF NOT EXISTS idx_messages_read_status ON messages(read_status, conversation_id, sender_id);

-- Update existing messages to be marked as read (since they're historical)
UPDATE messages SET read_status = 1 WHERE read_status IS NULL OR read_status = 0; 