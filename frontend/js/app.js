// Global variables
let currentUser = { id: 1, name: "You" }; // Assuming current user
let pinnedUsers = [];
let openConversations = []; // Tracks open conversation tabs
let allUsers = []; // Store all users data for reference

// DOM Elements
const usersList = document.getElementById("users-list");
const conversationContainer = document.getElementById("conversation-container");
const currentChatName = document.getElementById("current-chat-name");
const currentChatStatus = document.getElementById("current-chat-status");

// Count elements
const pinnedCount = document.getElementById("pinned-count");
const onlineCount = document.getElementById("online-count");
const offlineCount = document.getElementById("offline-count");

// Initialize the app
document.addEventListener("DOMContentLoaded", () => {
  fetchUsers();
  startPolling();
});

// Fetch users from backend
async function fetchUsers() {
  try {
    const response = await fetch("http://localhost/chatty/backend/users.php");
    const users = await response.json();

    if (users && users.length > 0) {
      allUsers = users; // Store all users for reference
      processUsersData(users);
    } else {
      document.getElementById("pinned-users").innerHTML =
        '<p class="no-users">No users found</p>';
    }
  } catch (error) {
    console.error("Error fetching users:", error);
  }
}

// Process users data and render
function processUsersData(users) {
  // Filter out current user
  const otherUsers = users.filter((user) => user.id !== currentUser.id);

  // Count online/offline users
  const onlineUsers = otherUsers.filter((user) => user.status === "online");
  const offlineUsers = otherUsers.filter((user) => user.status !== "online");

  // Update counts
  onlineCount.textContent = onlineUsers.length;
  offlineCount.textContent = offlineUsers.length;
  pinnedCount.textContent = pinnedUsers.length;

  // Update the header online count
  document.getElementById("total-online-count").textContent =
    onlineUsers.length;

  // Clear existing user lists
  document.getElementById("pinned-users").innerHTML = "";
  document.getElementById("online-users").innerHTML = "";
  document.getElementById("offline-users").innerHTML = "";

  // Render pinned users first
  pinnedUsers.forEach((userId) => {
    const user = otherUsers.find((u) => u.id == userId);
    if (user) {
      renderUserItem(user, "pinned-users", true);
    }
  });

  // Render online users (not pinned)
  onlineUsers.forEach((user) => {
    if (!pinnedUsers.includes(user.id)) {
      renderUserItem(user, "online-users", false);
    }
  });

  // Render offline users (not pinned)
  offlineUsers.forEach((user) => {
    if (!pinnedUsers.includes(user.id)) {
      renderUserItem(user, "offline-users", false);
    }
  });
}

// Render individual user item
function renderUserItem(user, containerId, isPinned) {
  const container = document.getElementById(containerId);
  const userItem = document.createElement("div");
  userItem.className = `user-item ${isPinned ? "pinned" : ""}`;
  userItem.dataset.userId = user.id;

  userItem.innerHTML = `
        <div class="user-avatar ${user.status === "online" ? "online" : ""}">
            ${user.name.charAt(0).toUpperCase()}
        </div>
        <div class="user-info">
            <div class="user-name">${user.name}</div>
            <div class="user-preview">${
              user.last_message || "No messages yet"
            }</div>
            <div class="user-time">${formatTime(user.last_seen)}</div>
        </div>
        <div class="user-actions">
            <button class="btn-icon toggle-pin ${
              isPinned ? "pinned" : ""
            }" data-user-id="${user.id}">
                <i class="fas fa-thumbtack"></i>
            </button>
            <button class="btn-icon">
                <i class="fas fa-info-circle"></i>
            </button>
            <button class="btn-icon">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
    `;

  userItem.addEventListener("click", () => {
    openConversationTab(user);
  });

  // Add pin event listener
  const pinButton = userItem.querySelector(".toggle-pin");
  pinButton.addEventListener("click", (e) => {
    e.stopPropagation();
    togglePinUser(user.id);
  });

  container.appendChild(userItem);
}

// Toggle user pin status
function togglePinUser(userId) {
  if (pinnedUsers.includes(userId)) {
    pinnedUsers = pinnedUsers.filter((id) => id !== userId);
  } else {
    pinnedUsers.push(userId);
  }
  fetchUsers(); // Re-render the lists
}

// Open or focus a conversation tab
async function openConversationTab(user) {
  // Check if conversation is already open
  const existingTab = openConversations.find((c) => c.userId == user.id);

  if (existingTab) {
    // Conversation already open - just bring to focus
    highlightActiveTab(user.id);
    return;
  }

  // Don't allow more than 3 open conversations
  if (openConversations.length >= 3) {
    alert(
      "You can only have 3 conversations open at a time. Close one to open another."
    );
    return;
  }

  // Add to open conversations
  openConversations.push({
    userId: user.id,
    userName: user.name,
    userStatus: user.status,
    profilePicture: user.profile_picture,
    lastSeen: user.last_seen,
  });

  // Update UI
  updateConversationTabs();

  // Fetch and display messages
  await fetchAndDisplayConversation(user.id);
}

// Generate Google Meet link
function generateGoogleMeetLink() {
  const characters = "abcdefghijklmnopqrstuvwxyz";
  let result = "";
  for (let i = 0; i < 10; i++) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return `https://meet.google.com/${result}`;
}

// Send Google Meet link
async function sendGoogleMeetLink(userId) {
  const meetLink = generateGoogleMeetLink();
  const messageText = `Let's meet on Google Meet: ${meetLink}`;

  try {
    const response = await fetch(
      "http://localhost/chatty/backend/send_messages.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          conversation_id: userId,
          sender_id: currentUser.id,
          content: messageText,
        }),
      }
    );

    const result = await response.json();

    if (result.success) {
      // Refresh messages
      const conversation = await fetchConversation(userId);
      if (conversation) {
        const container = document.getElementById(`messages-${userId}`);
        if (container) {
          renderMessages(container, conversation.messages);
        }
        fetchUsers(); // Update the last message preview
      }
    }
  } catch (error) {
    console.error("Error sending Google Meet link:", error);
  }
}

// Get user data by ID
function getUserById(userId) {
  return allUsers.find((user) => user.id == userId);
}

// Update the conversation tabs layout
function updateConversationTabs() {
  conversationContainer.innerHTML = "";
  console.log(openConversations.length);
  if (openConversations.length === 0) {
    conversationContainer.innerHTML = `
          <div class="chat-area-initial-message">
            <div>💬</div>
            <h2>Welcome to Chat App</h2>
            <p>Select a contact from the right panel to start chatting</p>
          </div>
        `;
    return;
  }

  // Determine width class based on number of open conversations
  const widthClass =
    openConversations.length === 1
      ? "single-tab"
      : openConversations.length === 2
      ? "double-tab"
      : "triple-tab";

  // Create a tab for each open conversation
  openConversations.forEach((conv) => {
    const user = getUserById(conv.userId);
    const isOnline = user?.status === "online";

    const tab = document.createElement("div");
    tab.className = `conversation-tab ${widthClass}`;
    tab.dataset.userId = conv.userId;

    // Create avatar style - either profile picture or initials
    const avatarStyle = conv.profilePicture
      ? `background-image: url('${conv.profilePicture}');`
      : `background-color: var(--primary-color);`;

    const avatarContent = conv.profilePicture
      ? ""
      : conv.userName.charAt(0).toUpperCase();

    tab.innerHTML = `
            <div class="conversation-tab-header">
                <div class="header-left">
                    <div class="header-profile">
                        <div class="header-avatar ${
                          isOnline ? "online" : ""
                        }" style="${avatarStyle}">
                            ${avatarContent}
                        </div>
                    </div>
                    <div class="header-user-info">
                        <div class="header-user-name">${conv.userName}</div>
                        <div class="header-user-status ${
                          isOnline ? "online" : ""
                        }">
                            ${
                              isOnline
                                ? "Online"
                                : `Last seen ${formatTime(conv.lastSeen)}`
                            }
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="header-btn meet-btn" data-user-id="${
                      conv.userId
                    }">
                        <i class="fas fa-phone"></i>
                        <span>Google Meet</span>
                    </button>
                    <button class="close-tab" data-user-id="${conv.userId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="tab-messages-container" id="messages-${conv.userId}">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>Loading conversation...</p>
                </div>
            </div>
            <div class="tab-message-input-container">
                <input type="text" class="tab-message-input" placeholder="Type a message..." 
                       data-user-id="${conv.userId}">
                <button class="tab-send-button" data-user-id="${conv.userId}">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        `;

    // Add close tab event
    tab.querySelector(".close-tab").addEventListener("click", (e) => {
      e.stopPropagation();
      closeConversationTab(conv.userId);
    });

    // Add Google Meet event
    tab.querySelector(".meet-btn").addEventListener("click", (e) => {
      e.stopPropagation();
      sendGoogleMeetLink(conv.userId);
    });

    // Add send message event
    const input = tab.querySelector(".tab-message-input");
    const sendBtn = tab.querySelector(".tab-send-button");

    sendBtn.addEventListener("click", () => sendMessage(conv.userId, input));
    input.addEventListener("keypress", (e) => {
      if (e.key === "Enter") sendMessage(conv.userId, input);
    });

    conversationContainer.appendChild(tab);
  });
}

// Close a conversation tab
function closeConversationTab(userId) {
  openConversations = openConversations.filter((c) => c.userId != userId);
  updateConversationTabs();
}

// Highlight the active tab in the sidebar
function highlightActiveTab(userId) {
  document.querySelectorAll(".user-item").forEach((item) => {
    item.classList.toggle("active", item.dataset.userId == userId);
  });
}

// Fetch and display conversation
async function fetchAndDisplayConversation(userId) {
  try {
    const conversation = await fetchConversation(userId);
    if (!conversation) return;

    const messagesContainer = document.getElementById(`messages-${userId}`);
    if (!messagesContainer) return;

    // Clear only if we have messages to show
    if (conversation.messages && conversation.messages.length > 0) {
      messagesContainer.innerHTML = "";
      const messagesWrapper = document.createElement("div");
      messagesWrapper.className = "messages-wrapper";

      conversation.messages.forEach((message) => {
        const messageElement = document.createElement("div");
        messageElement.className = `message ${
          message.sender_id === currentUser.id
            ? "message-outgoing"
            : "message-incoming"
        }`;

        messageElement.innerHTML = `
                    <div class="message-bubble">
                        <div class="message-text">${message.content}</div>
                        <div class="message-info">
                            <span>${formatTime(message.timestamp)}</span>
                            ${
                              message.sender_id === currentUser.id
                                ? `<span>${message.is_read ? "✓✓" : "✓"}</span>`
                                : ""
                            }
                        </div>
                    </div>
                `;

        messagesWrapper.appendChild(messageElement);
      });

      messagesContainer.appendChild(messagesWrapper);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } else {
      // Show empty state only if there are truly no messages
      messagesContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No messages yet. Start the conversation!</p>
                </div>
            `;
    }

    highlightActiveTab(userId);
  } catch (error) {
    console.error("Error loading conversation:", error);
    const messagesContainer = document.getElementById(`messages-${userId}`);
    if (messagesContainer) {
      messagesContainer.innerHTML = `
                <div class="empty-state error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Failed to load messages</p>
                </div>
            `;
    }
  }
}

// Fetch conversation for a user
async function fetchConversation(userId) {
  try {
    const response = await fetch(
      `http://localhost/chatty/backend/conversations.php?user_id=${userId}`
    );
    const data = await response.json();
    return data.conversation ? data : null;
  } catch (error) {
    console.error("Error fetching conversation:", error);
    return null;
  }
}

// Render messages in a container
function renderMessages(container, messages) {
  container.innerHTML = "";

  if (!messages || messages.length === 0) {
    container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <p>No messages yet. Start the conversation!</p>
            </div>
        `;
    return;
  }

  const messagesWrapper = document.createElement("div");
  messagesWrapper.className = "messages-wrapper";

  messages.forEach((message) => {
    const messageElement = document.createElement("div");
    messageElement.className = `message ${
      message.sender_id === currentUser.id
        ? "message-outgoing"
        : "message-incoming"
    }`;

    messageElement.innerHTML = `
            <div class="message-bubble">
                <div class="message-text">${message.content}</div>
                <div class="message-info">
                    <span>${formatTime(message.timestamp)}</span>
                    ${
                      message.sender_id === currentUser.id
                        ? `<span>${message.is_read ? "✓✓" : "✓"}</span>`
                        : ""
                    }
                </div>
            </div>
        `;

    messagesWrapper.appendChild(messageElement);
  });

  container.appendChild(messagesWrapper);
  container.scrollTop = container.scrollHeight;
}

// Send a message in a conversation
async function sendMessage(userId, inputElement) {
  const messageText = inputElement.value.trim();
  if (!messageText) return;

  try {
    const response = await fetch(
      "http://localhost/chatty/backend/send_messages.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          conversation_id: userId,
          sender_id: currentUser.id,
          content: messageText,
        }),
      }
    );

    const result = await response.json();

    if (result.success) {
      // Clear input
      inputElement.value = "";

      // Refresh messages
      const conversation = await fetchConversation(userId);
      if (conversation) {
        const container = document.getElementById(`messages-${userId}`);
        if (container) {
          renderMessages(container, conversation.messages);
        }
        fetchUsers(); // Update the last message preview
      }
    }
  } catch (error) {
    console.error("Error sending message:", error);
  }
}

function startPolling() {
  // First immediate poll
  checkForUpdates();

  // Polling every 3 seconds
  setInterval(checkForUpdates, 3000);
}

// Check for updates function that handles all open conversations
async function checkForUpdates() {
  if (openConversations.length > 0) {
    try {
      // Refresh users list for status updates
      await fetchUsers();

      // Update conversation headers with latest user data
      updateConversationHeaders();

      // Refresh all open conversations in parallel
      await Promise.all(
        openConversations.map(async (conv) => {
          try {
            const messagesContainer = document.getElementById(
              `messages-${conv.userId}`
            );
            if (messagesContainer) {
              const conversation = await fetchConversation(conv.userId);
              if (conversation?.messages) {
                // Only update if we got messages back
                renderMessages(messagesContainer, conversation.messages);
              }
            }
          } catch (error) {
            console.error(`Error updating conversation ${conv.userId}:`, error);
          }
        })
      );
    } catch (error) {
      console.error("Polling error:", error);
    }
  }
}

// Update conversation headers with latest user data
function updateConversationHeaders() {
  openConversations.forEach((conv) => {
    const user = getUserById(conv.userId);
    if (user) {
      // Update conversation data
      conv.userStatus = user.status;
      conv.lastSeen = user.last_seen;

      // Update header elements
      const headerAvatar = document.querySelector(
        `[data-user-id="${conv.userId}"] .header-avatar`
      );
      const headerStatus = document.querySelector(
        `[data-user-id="${conv.userId}"] .header-user-status`
      );

      if (headerAvatar) {
        headerAvatar.classList.toggle("online", user.status === "online");
      }

      if (headerStatus) {
        const isOnline = user.status === "online";
        headerStatus.textContent = isOnline
          ? "Online"
          : `Last seen ${formatTime(user.last_seen)}`;
        headerStatus.classList.toggle("online", isOnline);
      }
    }
  });
}

// Helper function to format time
function formatTime(timestamp) {
  if (!timestamp) return "Just now";
  const date = new Date(timestamp);
  const now = new Date();

  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  } else if (date > new Date(now - 7 * 24 * 60 * 60 * 1000)) {
    return date.toLocaleDateString([], { weekday: "short" });
  } else {
    return date.toLocaleDateString([], { month: "short", day: "numeric" });
  }
}
