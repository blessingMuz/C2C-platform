<?php
session_start();
include("config/db.php");

// Redirect to login if they aren't authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Safely define profile values from the active session as immediate fallbacks
$sessionName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['name']) ? $_SESSION['name'] : "Seller");
$avatarLetter = !empty($sessionName) ? strtoupper(substr($sessionName, 0, 1)) : "S";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Messages | Ubuntu Market Sender</title>
  <link rel="stylesheet" href="seller-shared.css">
  
  <style>
  
    .conversations-layout {
      display: grid;
      grid-template-columns: 340px 1fr;
      gap: 24px;
      margin-top: 10px;
      height: calc(100vh - 180px); 
      min-height: 520px;
    }

    .conv-list-panel {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 1px 8px rgba(0,0,0,0.06);
      border: 1px solid #f0f0f0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .conv-list-header {
      padding: 18px 24px;
      border-bottom: 1px solid #f0f0f0;
      font-size: 16px;
      font-weight: 700;
      color: #111;
    }

    .conv-list-body {
      flex: 1;
      overflow-y: auto;
      padding: 16px;
    }

    .conv-item {
      padding: 14px 16px;
      cursor: pointer;
      border-radius: 10px;
      transition: all 0.2s ease;
      margin-bottom: 8px;
      background: #fff;
      border: 1px solid #f5f5f5;
    }

    .conv-item:hover {
      background: #fafafa;
    }

    .conv-item.active {
      background: #fafafa;
      border-left: 4px solid #ff6600; 
      border-radius: 0 10px 10px 0;
    }

    .conv-item strong {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #000;
      margin-bottom: 4px;
    }

    .conv-item small {
      color: #888;
      font-size: 12px;
    }

    /
    .chat-display-panel {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 1px 8px rgba(0,0,0,0.06);
      border: 1px solid #f0f0f0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .chat-active-container {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .chat-timeline {
      flex: 1;
      overflow-y: auto;
      padding: 24px;
      background: #fff;
      display: flex;
      flex-direction: column;
    }

    .msg-wrapper {
      margin-bottom: 14px;
      display: flex;
      flex-direction: column;
    }

    .msg-wrapper.sent {
      align-items: flex-end;
    }

    .msg-wrapper.sent .bubble {
      background: #000;
      color: #fff;
      padding: 12px 18px;
      border-radius: 16px 16px 4px 16px;
      font-size: 14px;
      line-height: 1.4;
      max-width: 65%;
      word-break: break-word;
    }

    .msg-wrapper.received {
      align-items: flex-start;
    }

    .msg-wrapper.received .bubble {
      background: #e9e9e9;
      color: #000;
      padding: 12px 18px;
      border-radius: 16px 16px 16px 4px;
      font-size: 14px;
      line-height: 1.4;
      max-width: 65%;
      word-break: break-word;
    }

    .chat-action-bar {
      padding: 18px 24px;
      background: #fff;
      border-top: 1px solid #f0f0f0;
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .chat-action-bar input {
      flex: 1;
      padding: 12px 16px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      outline: none;
      background: #fafafa;
      transition: all 0.2s;
    }

    .chat-action-bar input:focus {
      border-color: #000;
      background: #fff;
    }

    .chat-action-bar button {
      padding: 12px 24px;
      background: #000;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .chat-action-bar button:hover {
      background: #ff6600; 
    }

    .fallback-blank-view {
      text-align: center;
      color: #aaa;
      padding-top: 180px;
      font-size: 14px;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-name">Ubuntu Market</div>
      <div class="brand-sub">Seller Panel</div>
    </div>
    
    <div class="seller-avatar">
      <div class="avatar-circle" id="sb-avatar"><?php echo $avatarLetter; ?></div>
      <div class="avatar-info">
        <div class="av-name" id="sb-name"><?php echo htmlspecialchars($sessionName); ?></div>
        <div class="av-role">Seller</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <ul>
        <li><a href="sellerDash.html">Dashboard</a></li>
        <li><a href="addProd.html"> Add Product</a></li>
        <li><a href="ManageProd.php"> Manage Products</a></li>
      </ul>
      
      <div class="nav-section-label">Operations</div>
      <ul>
        <li><a href="inventory.html"> Inventory</a></li>
        <li><a href="sellerOrders.html">Orders</a></li>
        <li><a href="sellerMess.php" class="active"> Messages</a></li>
      </ul>

      <div class="nav-section-label">Account</div>
      <ul>
        <li><a href="Sellerprofile.html"> Profile</a></li>
        <li><a href="help.php">Help</a></li>
      </ul>
    </nav>

    <div class="sidebar-bottom">
      <ul>
        <li><a href="logout.php" style="color: var(--accent-red, #ff4444);">Logout</a></li>
      </ul>
    </div>
  </div>

  <div class="main-content">
    <div class="page-body">
      
      <div class="topbar">
        <div class="topbar-left">
          <h1>Messages</h1>
          <p>Chat with buyers about products</p>
        </div>
      </div>

      <div class="conversations-layout">
        
        <div class="conv-list-panel">
          <div class="conv-list-header">Conversations</div>
          <div class="conv-list-body" id="conv-list-container">
            Loading conversations...
          </div>
        </div>

        <div class="chat-display-panel">
          <div id="chat-active-view" class="chat-active-container" style="display: none;">
            <div class="conv-list-header">Chatting regarding: <span id="buyer-name-header">-</span></div>
            <div class="chat-timeline" id="messages-target"></div>
            
            <div class="chat-action-bar">
              <input type="text" id="msg-input" placeholder="Type a message..." onkeypress="if(event.key === 'Enter') sendMessage()">
              <button onclick="sendMessage()">Send</button>
            </div>
          </div>

          <div id="chat-fallback-view" class="fallback-blank-view">
            Select an active conversation to view message history
          </div>
        </div>

      </div>

    </div>
  </div>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    let partnerId = urlParams.get('buyer_id'); 
    let productId = urlParams.get('product_id');

    // Load dynamic user profile details across sidebar panels securely
    function loadSidebarProfile() {
      fetch('getProfile.php')
        .then(response => response.json())
        .then(data => {
          //  forces a redirect to authentication view if session expired
          if (!data || data.status === 'login' || !data.name) {
            window.location.href = 'login.html';
            return;
          }
          
          // Smoothly update the workspace name and first-character avatar initial tags
          document.getElementById('sb-name').innerText = data.name;
          document.getElementById('sb-avatar').innerText = data.name.charAt(0).toUpperCase();
        })
        .catch(err => {
          console.log("Could not load dynamic sidebar credentials.");
        });
    }

    function loadSidebarConversations() {
        fetch('getConv.php')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('conv-list-container');
            
            if(!data || data.length === 0 || data.status === 'error') {
                container.innerHTML = '<div style="color:#aaa; font-size:13px; text-align:center; padding-top:20px;">No active chat selected.</div>';
                return;
            }

            container.innerHTML = data.map(c => {
                const isActive = (c.buyer_id == partnerId && c.product_id == productId) ? 'active' : '';
                const productName = c.product_name ? c.product_name : ("Product #" + c.product_id);
                const buyerName = c.buyer_name ? c.buyer_name : "Customer";
                
                return `
                    <div class="conv-item ${isActive}" onclick="openChat(${c.buyer_id}, ${c.product_id}, '${encodeURIComponent(productName)}')">
                        <strong>Chatting regarding ${productName}</strong>
                        <small>Buyer: ${buyerName}</small>
                    </div>
                `;
            }).join('');

            if (!partnerId && !productId && data.length > 0) {
                const firstChat = data[0];
                openChat(firstChat.buyer_id, firstChat.product_id, firstChat.product_name || "Product #" + firstChat.product_id);
            }
        })
        .catch(err => {
            document.getElementById('conv-list-container').innerHTML = '<div style="color:#aaa; font-size:13px; text-align:center; padding-top:20px;">No active chat selected.</div>';
        });
    }

    function openChat(bId, pId, pName) {
        partnerId = bId;
        productId = pId;
        
        window.history.pushState({}, '', window.location.pathname + `?buyer_id=${bId}&product_id=${pId}`);
        
        const headerTitle = document.getElementById('buyer-name-header');
        if (headerTitle) {
            headerTitle.innerText = decodeURIComponent(pName);
        }
        
        document.getElementById('chat-fallback-view').style.display = 'none';
        document.getElementById('chat-active-view').style.display = 'flex';

        loadSidebarConversations();
        fetchChatMessages();
    }

    function fetchChatMessages() {
        if(!partnerId || !productId) return;
        
        fetch(`getMessages.php?chat_partner=${partnerId}&product_id=${productId}`)
        .then(r => r.json())
        .then(messages => {
            const messagesContainer = document.getElementById('messages-target');
            if(!messagesContainer) return;
            
            if (!messages || messages.length === 0 || messages.status === 'error') {
                messagesContainer.innerHTML = '<div style="text-align:center; color:#aaa; padding-top:100px;">No messages yet.</div>';
                return;
            }

            messagesContainer.innerHTML = messages.map(msg => {
                const sideClass = msg.is_mine ? 'sent' : 'received';
                return `
                    <div class="msg-wrapper ${sideClass}">
                        <div class="bubble">${msg.message}</div>
                    </div>
                `;
            }).join('');
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        })
        .catch(err => {
            console.log("No messages retrieved or loaded yet.");
        });
    }

    function sendMessage() {
        const messageInput = document.getElementById('msg-input');
        if(!messageInput) return;
        
        const msgText = messageInput.value.trim();
        if(!msgText || !partnerId || !productId) return;

        fetch('sendMessage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `receiver_id=${partnerId}&product_id=${productId}&message=${encodeURIComponent(msgText)}`
        })
        .then(r => r.json())
        .then(data => {
            if(data && data.status === 'success') {
                messageInput.value = '';
                fetchChatMessages();
            }
        });
    }

    // Fire synchronization processes instantly on layout completion
    loadSidebarProfile();
    loadSidebarConversations();

    if(partnerId && productId) {
        document.getElementById('chat-fallback-view').style.display = 'none';
        document.getElementById('chat-active-view').style.display = 'flex';
        fetchChatMessages();
    }

    setInterval(fetchChatMessages, 3000);
</script>

<footer class="seller-footer">
  <p>&copy; 2026 Ubuntu Market &nbsp;|&nbsp;
    <a href="about.html">About</a>
    <a href="contact.html">Contact</a>
  </p>
</footer>
</body>
</html>