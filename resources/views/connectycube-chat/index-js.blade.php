<script>
class ConnectyCubeChatManager {
    constructor() {
        // Initialize with data from backend
        this.drivers = @json($globalChatDrivers ?? []);
        this.currentUser = @json($globalCurrentUser ?? null);
        this.credentials = null;
        this.userPassword = this.currentUser?.connectycube_password;
        this.isChatVisible = false;

        // Verify we have required user data
        if (!this.currentUser?.connectycube_id || !this.currentUser?.connectycube_password) {
            console.error('Missing ConnectyCube credentials for current user');
            throw new Error('User not properly configured for chat');
        }

        // State management
        this.isInitialized = false;
        this.isChatVisible = false;
        this.isMainChatCreated = false;
        this.activeDialogs = new Map();
        this.openChatWindows = new Map();
        this.onlineUsers = new Set();
        this.onlineStatusMap = new Map();
        this.typingUsers = new Map();
        this.typingTimeout = null;
        this.typingPollers = new Map();
        this.currentView = 'users';
        this.statusUpdateInterval = null;
        this.presenceUpdateInterval = null;

        // Dynamic chat window management
        this.windowWidth = 320;
        this.windowSpacing = 20;
        this.rightOffset = 420;
        this.minSpacePercentage = 30;

        // Store all users
        this.allUsers = [];
        
        // Theme tracking
        this.currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        
        // Listen for theme changes
        this._setupThemeListener();
    }

    // Setup theme change listener
    _setupThemeListener() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'data-theme') {
                    this.currentTheme = document.documentElement.getAttribute('data-theme');
                    this._updateChatTheme();
                }
            });
        });

        observer.observe(document.documentElement, { attributes: true });
    }

    // Update all chat components with current theme
    _updateChatTheme() {
        // Update main chat container
        const mainContainer = document.getElementById('connectycube-chat-container');
        if (mainContainer) {
            mainContainer.setAttribute('data-theme', this.currentTheme);
        }

        // Update individual chat windows
        this.openChatWindows.forEach((_, userId) => {
            const chatWindow = document.getElementById(`chat-window-${userId}`);
            if (chatWindow) {
                chatWindow.setAttribute('data-theme', this.currentTheme);
            }
        });

        // Update launcher
        const launcher = document.getElementById('chat-launcher');
        if (launcher) {
            launcher.setAttribute('data-theme', this.currentTheme);
        }

        // Re-render users list with updated theme
        if (this.currentView === 'users' && this.isMainChatCreated) {
            this._renderUsersList();
        }
    }

    // Calculate maximum chat windows based on screen size
    _calculateMaxChatWindows() {
        const screenWidth = window.innerWidth;
        const availableWidth = screenWidth - this.rightOffset;
        const spaceToLeave = screenWidth * (this.minSpacePercentage / 100);
        const usableWidth = availableWidth - spaceToLeave;

        const maxWindows = Math.floor(usableWidth / (this.windowWidth + this.windowSpacing));

        // Ensure at least 1 window can be opened, but cap at reasonable limit
        return Math.max(1, Math.min(maxWindows, 5));
    }

    async fetchUsersStatus() {
        try {
            const token = @json($userToken ?? null) ||
                localStorage.getItem('driver_token') ||
                localStorage.getItem('auth') ||
                localStorage.getItem('token');

            const apiUrl = 'https://cargodispatch.co/dispatch/public/api/chat/users-status';

            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                credentials: 'include'
            });

            if (!response.ok) {
                let errorMessage = `Server returned ${response.status}: ${response.statusText}`;
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } catch (e) {
                    const text = await response.text();
                    if (text) errorMessage = `${errorMessage}. Response: ${text}`;
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();

            if (data.success) {
                this.usersWithStatus = data.users;
                this.allUsers = data.users;
                this.drivers = this.allUsers.filter(user => user.role_type === 'driver');
                this.admins = this.allUsers.filter(user => user.role_type === 'admin');

                this.onlineStatusMap = new Map();
                this.usersWithStatus.forEach(user => {
                    this.onlineStatusMap.set(user.connectycube_id, user.is_online);
                });

                if (this.currentView === 'users' && this.isMainChatCreated) {
                    this._renderUsersList();
                }

                return data;
            } else {
                throw new Error(data.message || 'Failed to fetch users status');
            }
        } catch (error) {
            console.error('Failed to fetch users status:', error);
            this._showError(`Failed to load user status: ${error.message}`);
            throw error;
        }
    }

    // Initialize - only create launcher initially
    async initialize() {
        try {
            await this.fetchUsersStatus();
            await this._fetchSessionData();
            await this._fetchCredentials();

            await this._loadSDK();
            await this._initConnectyCube();
            await this._createSession();
            await this._connectToChat();
            this._setupListeners();
            
            // Only create launcher initially - no main chat window
            await this._createLauncherOnly();

            this.isInitialized = true;
            this.isChatVisible = false;

        } catch (error) {
            this._showError(`Chat initialization failed: ${error.message}`);
        }
    }

    async _fetchSessionData() {
        try {
            const response = await fetch('{{ route("chat.session") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch session data: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.credentials = data.credentials;
                this.userPassword = data.user.connectycube_password;
                
                // Update current user with fresh data from session
                this.currentUser = { 
                    ...this.currentUser, 
                    ...data.user 
                };
            } else {
                throw new Error(data.message || 'Failed to fetch session data');
            }
        } catch (error) {
            console.error('Failed to fetch session data:', error);
            throw new Error(`Session data fetch failed: ${error.message}`);
        }
    }

    async _fetchCredentials() {
        try {
            const token = @json($userToken ?? null) ||
                localStorage.getItem('driver_token') ||
                localStorage.getItem('auth') ||
                localStorage.getItem('token');

            const response = await fetch('{{ route("chat.session") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch credentials: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.credentials = data.credentials;
                
                // Update user data if needed
                if (data.user) {
                    this.currentUser = { ...this.currentUser, ...data.user };
                    this.userPassword = data.user.connectycube_password;
                }
            } else {
                throw new Error(data.message || 'Failed to fetch credentials');
            }
        } catch (error) {
            console.error('Failed to fetch credentials:', error);
            throw new Error(`Credentials fetch failed: ${error.message}`);
        }
    }

    // Create only launcher initially
    async _createLauncherOnly() {
        this._removeExistingUI();

        const container = document.createElement('div');
        container.innerHTML = `
            <div class="chat-launcher bg-gradient-primary chat-launcher display-flex" id="chat-launcher" data-theme="${this.currentTheme}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
        `;

        document.body.appendChild(container);
        this._setupLauncherEvents();

        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    // Create main chat window - make sure it's always available
    _createMainChatWindow() {
        // Remove existing if any to avoid duplicates
        const existing = document.getElementById('connectycube-chat-container');
        if (existing) {
            existing.remove();
        }
        
        const container = document.createElement('div');
        container.id = 'connectycube-chat-container';
        container.setAttribute('data-theme', this.currentTheme);
        container.style.display = 'none'; // Start hidden but ready
        container.innerHTML = `
            <div class="chat-window chat-window-fill">
                <div class="chat-header">
                    <div class="header-content">
                        <h3 id="chat-title">Cargo Dispatch</h3>
                    </div>
                    <div class="chat-controls">
                        <button id="chat-minimize" title="Minimize" aria-label="Minimize">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                        <button id="chat-close" title="Close" aria-label="Close">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="chat-body">
                    <div class="users-list" id="users-view">
                        <div class="users-header">
                            <span>Available Users</span>
                            <button id="refresh-users" class="refresh-btn" title="Refresh Users" aria-label="Refresh Users">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                </svg>
                            </button>
                        </div>
                        <div class="users-container" id="users-list"></div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(container);
        this.isMainChatCreated = true;
        this._renderUsersList();
        this._setupMainChatEvents();
    }

    // Setup launcher events
    _setupLauncherEvents() {
        const launcher = document.getElementById('chat-launcher');
        if (launcher) {
            launcher.addEventListener('click', () => {
                this._showMainChat();
            });
        }
    }

    // Setup main chat events
    _setupMainChatEvents() {
        const minimizeBtn = document.getElementById('chat-minimize');
        const closeBtn = document.getElementById('chat-close');
        const refreshBtn = document.getElementById('refresh-users');

        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', () => {
                this._hideMainChat();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this._hideMainChat();
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', async () => {
                try {
                    await this.fetchUsersStatus();
                } catch (error) {
                    console.error('Failed to refresh users:', error);
                }
            });
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            const maxWindows = this._calculateMaxChatWindows();

            if (this.openChatWindows.size > maxWindows) {
                const windowsToClose = this.openChatWindows.size - maxWindows;
                const oldestWindows = Array.from(this.openChatWindows.keys()).slice(0, windowsToClose);

                oldestWindows.forEach(userId => {
                    this._closeChatWindow(userId);
                });
            }

            this._positionChatWindows();
        });
    }

    // Show main chat
    _showMainChat() {
        // ALWAYS create main chat if it doesn't exist
        if (!this.isMainChatCreated) {
            this._createMainChatWindow();
        }
        
        const chatContainer = document.getElementById('connectycube-chat-container');
        const launcher = document.getElementById('chat-launcher');
        
        if (chatContainer) chatContainer.style.display = 'flex';
        if (launcher) launcher.style.display = 'none';
        
        this.isChatVisible = true;
        this.startStatusUpdates();
    }

    // Hide main chat
    _hideMainChat() {
        const chatContainer = document.getElementById('connectycube-chat-container');
        const launcher = document.getElementById('chat-launcher');
        
        if (chatContainer) chatContainer.style.display = 'none';
        if (launcher) launcher.style.display = 'flex';
        
        this.isChatVisible = false;
        this.stopStatusUpdates();
    }

    hideChat() {
        this._hideMainChat();
    }

    async _loadSDK() {
        if (window.ConnectyCube) return;

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/connectycube@4/dist/connectycube.min.js';
            script.async = true;
            script.onload = resolve;
            script.onerror = () => reject(new Error('Failed to load ConnectyCube SDK'));
            document.head.appendChild(script);
        });
    }

    async _initConnectyCube() {
        await ConnectyCube.init(this.credentials, {
            endpoints: {
                api: "api.connectycube.com",
                chat: "chat.connectycube.com"
            },
            debug: {
                mode: 1
            }
        });
    }

    async _createSession() {
        try {
            const session = await ConnectyCube.auth.createSession({
                login: this.currentUser.connectycube_login,
                password: this.currentUser.connectycube_password
            });

            this.currentUser = session.user;
        } catch (error) {
            console.error('Session creation failed:', {
                login: this.currentUser.connectycube_login,
                error: error.message
            });
            throw new Error(`Authentication failed: ${error.message}`);
        }
    }

    async _connectToChat() {
        try {
            const password = this.currentUser?.connectycube_password ||
                this.userPassword ||
                @json($currentUser->connectycube_password ?? '');

            if (!password) {
                throw new Error('Password is not available for chat connection');
            }

            await ConnectyCube.chat.connect({
                userId: this.currentUser.id,
                password: password
            });
        } catch (error) {
            console.error('Chat connection failed:', {
                userId: this.currentUser?.id,
                error: error.message
            });
            throw new Error(`Chat connection failed: ${error.message}`);
        }
    }

    _setupListeners() {
        ConnectyCube.chat.onMessageListener = (userId, message) => {
            console.log('Incoming message from user ID:', userId, message);

            if (userId === this.currentUser.id) return;

            const senderUser = this.allUsers.find(user => user.connectycube_id === userId);

            if (senderUser) {
                console.log('Sender found:', senderUser);

                // ALWAYS show main chat window when message is received
                if (!this.isChatVisible) {
                    this._showMainChat(); // This will open the main chat window
                }

                // ALWAYS open chat window for the sender
                if (!this.openChatWindows.has(senderUser.id)) {
                    console.log('Auto-opening chat window for:', senderUser.firstname || senderUser.first_name || senderUser.name);
                    this._openChatWindow(senderUser);
                }

                this._displayMessageInWindow(senderUser.id, message, true);
                this._showMessageNotification(senderUser, message.message || message.body);
                this._focusChatWindow(senderUser.id);
                this._flashWindowTitle(senderUser.id);
                
            } else {
                console.warn('Unknown sender with ID:', userId);
                const tempUser = {
                    id: userId,
                    connectycube_id: userId,
                    firstname: 'User',
                    first_name: 'User',
                    name: 'User',
                    role_type: 'user'
                };

                // ALWAYS show main chat window when message is received
                if (!this.isChatVisible) {
                    this._showMainChat();
                }

                if (!this.openChatWindows.has(tempUser.id)) {
                    console.log('Auto-opening chat window for temporary user');
                    this._openChatWindow(tempUser);
                }
                this._displayMessageInWindow(tempUser.id, message, true);
            }
        };
    }

    _positionChatWindows() {
        const openWindows = Array.from(this.openChatWindows.keys());

        openWindows.forEach((userId, index) => {
            const chatWindow = document.getElementById(`chat-window-${userId}`);
            if (chatWindow) {
                const position = this.rightOffset + (index * (this.windowWidth + this.windowSpacing));
                const maxRight = window.innerWidth - this.windowWidth - 20;
                const actualRight = Math.min(position, maxRight);

                chatWindow.style.right = `${actualRight}px`;
                chatWindow.style.bottom = '20px';
                chatWindow.style.left = 'auto';
                chatWindow.style.transform = 'none';
                chatWindow.style.zIndex = 9999 + index;

                console.log(`Positioned chat window ${userId} at right: ${actualRight}px`);
            }
        });
    }

    async _openChatWindow(user) {
        try {
            if (this.openChatWindows.has(user.id)) {
                this._focusChatWindow(user.id);
                return;
            }

            const maxChatWindows = this._calculateMaxChatWindows();

            if (this.openChatWindows.size >= maxChatWindows) {
                const oldestUserId = this.openChatWindows.keys().next().value;
                console.log(`Closing oldest chat window for user ${oldestUserId} (max: ${maxChatWindows})`);
                this._closeChatWindow(oldestUserId);
            }

            const dialog = await this._getOrCreateDialog(user);

            this.openChatWindows.set(user.id, {
                user: user,
                dialog: dialog,
                isMinimized: false,
                openedAt: Date.now()
            });

            this._createChatWindowUI(user, dialog);
            await this._loadMessageHistoryForWindow(user.id, dialog);
            this._positionChatWindows();
            this._startTypingPolling(user.id, dialog._id, user.connectycube_id);

            console.log(`Opened chat window for ${user.firstname || user.first_name || user.name}. Total open: ${this.openChatWindows.size}`);

        } catch (error) {
            console.error('Failed to open chat window:', error);
            this._showError(`Could not open chat with ${user.firstname || user.first_name || user.name}: ${error.message}`);
        }
    }

    _createChatWindowUI(user, dialog) {
        const windowId = `chat-window-${user.id}`;
        const isOnline = this.onlineStatusMap.get(user.connectycube_id) || false;
        const userName = user.firstname || user.first_name || user.name || 'Unknown';
        const userLastName = user.lastname || user.last_name || '';

        const chatWindow = document.createElement('div');
        chatWindow.id = windowId;
        chatWindow.className = 'individual-chat-window';
        chatWindow.setAttribute('data-theme', this.currentTheme);

        chatWindow.innerHTML = `
            <div class="chat-window-header">
                <div class="chat-user-info">
                    <div class="header-avatar">
                        <div class="header-avatar-circle">
                            ${userName.charAt(0).toUpperCase()}
                        </div>
                        <div class="header-online-indicator ${isOnline ? 'online' : 'offline'}"></div>
                    </div>
                    <div class="header-text">
                        <div class="chat-user-name">${userName} ${userLastName}</div>
                        <div class="chat-user-status ${isOnline ? 'online' : 'offline'}">${isOnline ? 'Online' : 'Offline'}</div>
                    </div>
                </div>
                <div class="chat-window-controls">
                    <button class="minimize-chat" data-user-id="${user.id}" title="Minimize" aria-label="Minimize">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                    <button class="close-chat" data-user-id="${user.id}" title="Close" aria-label="Close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="chat-window-body">
                <div class="chat-messages" id="messages-${user.id}"></div>
                <div class="chat-typing-indicator chat-typing-hidden" id="typing-${user.id}">
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <span class="typing-text">${userName} is typing...</span>
                </div>
                <div class="chat-input-area">
                    <input type="text" class="chat-message-input" data-user-id="${user.id}" data-dialog-id="${dialog._id}" placeholder="Type a message..." maxlength="1000">
                    <button class="send-chat-message" data-user-id="${user.id}" title="Send" aria-label="Send message">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m22 2-7 20-4-9-9-4 20-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(chatWindow);
        this._setupChatWindowEvents(user.id);

        setTimeout(() => {
            const input = chatWindow.querySelector('.chat-message-input');
            if (input) input.focus();
        }, 100);
    }

    _setupChatWindowEvents(userId) {
        const chatWindow = document.getElementById(`chat-window-${userId}`);
        if (!chatWindow) return;

        const sendBtn = chatWindow.querySelector('.send-chat-message');
        const messageInput = chatWindow.querySelector('.chat-message-input');

        if (sendBtn) {
            sendBtn.addEventListener('click', () => this._sendMessageFromWindow(userId));
        }

        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this._sendMessageFromWindow(userId);
                }
            });

            messageInput.addEventListener('input', () => {
                const dialogId = messageInput.getAttribute('data-dialog-id');
                this._sendTypingIndicatorForWindow(userId, true, dialogId);

                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => {
                    this._sendTypingIndicatorForWindow(userId, false, dialogId);
                }, 3000);
            });
        }

        const minimizeBtn = chatWindow.querySelector('.minimize-chat');
        const closeBtn = chatWindow.querySelector('.close-chat');

        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', () => this._minimizeChatWindow(userId));
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this._closeChatWindow(userId));
        }

        this._makeDraggable(chatWindow);
    }

    _minimizeChatWindow(userId) {
        const chatWindow = document.getElementById(`chat-window-${userId}`);
        const windowData = this.openChatWindows.get(userId);

        if (chatWindow && windowData) {
            const body = chatWindow.querySelector('.chat-window-body');
            const minimizeBtn = chatWindow.querySelector('.minimize-chat');
            const maximizeBtn = chatWindow.querySelector('.maximize-chat');

            if (body.style.display === 'none') {
                body.style.display = 'flex';
                chatWindow.style.height = '450px';
                minimizeBtn.style.display = 'inline-block';
                maximizeBtn.style.display = 'none';
                windowData.isMinimized = false;
            } else {
                body.style.display = 'none';
                chatWindow.style.height = '50px';
                minimizeBtn.style.display = 'none';
                maximizeBtn.style.display = 'inline-block';
                windowData.isMinimized = true;
            }
        }
    }

    async _sendMessageFromWindow(userId) {
        const input = document.querySelector(`.chat-message-input[data-user-id="${userId}"]`);
        if (!input) return;
        
        const text = input.value.trim();
        if (!text) return;

        const windowData = this.openChatWindows.get(userId);
        if (!windowData) return;

        try {
            this._sendTypingIndicatorForWindow(userId, false, windowData.dialog._id);

            const message = await ConnectyCube.chat.message.create({
                chat_dialog_id: windowData.dialog._id,
                message: text,
                send_to_chat: 1
            });

            this._displayMessageInWindow(userId, {
                message: text,
                body: text,
                date_sent: Math.floor(Date.now() / 1000),
                sender_id: this.currentUser.id
            }, false);

            input.value = '';
        } catch (error) {
            console.error('Failed to send message:', error);
            this._showError('Failed to send message');
        }
    }

    _displayMessageInWindow(userId, message, isIncoming) {
        const messagesContainer = document.getElementById(`messages-${userId}`);
        if (!messagesContainer) {
            console.warn(`Messages container not found for user ${userId}`);
            return;
        }

        const messageEl = document.createElement('div');
        messageEl.className = `message ${isIncoming ? 'incoming' : 'outgoing'}`;

        const time = message.date_sent ? new Date(message.date_sent * 1000) : new Date();
        const timeStr = time.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        const messageContent = message.message || message.body || '[No content]';

        messageEl.innerHTML = `
            <div class="message-content">${this._escapeHtml(messageContent)}</div>
            <div class="message-time">${timeStr}</div>
        `;

        messagesContainer.appendChild(messageEl);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        const windowData = this.openChatWindows.get(userId);
        if (windowData && windowData.isMinimized && isIncoming) {
            this._flashWindowTitle(userId);
        }
    }

    async _loadMessageHistoryForWindow(userId, dialog) {
        if (!dialog) return;

        try {
            const messages = await ConnectyCube.chat.message.list({
                chat_dialog_id: dialog._id,
                limit: 50,
                sort_desc: 'date_sent'
            });

            const container = document.getElementById(`messages-${userId}`);
            if (container) {
                container.innerHTML = '';

                messages.items.reverse().forEach(msg => {
                    this._displayMessageInWindow(userId, msg, msg.sender_id !== this.currentUser.id);
                });

                container.scrollTop = container.scrollHeight;
            }
        } catch (error) {
            console.error('Failed to load messages for user:', userId, error);
        }
    }

    _updateTypingIndicatorInWindow(userId, isTyping) {
        const indicator = document.getElementById(`typing-${userId}`);
        if (indicator) {
            indicator.style.display = isTyping ? 'flex' : 'none';
            if (isTyping) {
                const messagesContainer = document.getElementById(`messages-${userId}`);
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        }
    }

    _sendTypingIndicatorForWindow(userId, isTyping, dialogId) {
        try {
            if (dialogId) {
                ConnectyCube.chat.sendIsTypingStatus(dialogId, isTyping);
                const windowData = this.openChatWindows.get(userId);
                if (windowData?.user?.connectycube_id) {
                    this._syncTypingToBackend(dialogId, windowData.user.connectycube_id, isTyping);
                }
            }
        } catch (error) {
            console.error('Failed to send typing indicator:', error);
        }
    }

    async _syncTypingToBackend(dialogId, toConnectycubeId, isTyping) {
        try {
            await fetch('/api/chat/typing', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    dialog_id: dialogId,
                    to_connectycube_id: toConnectycubeId,
                    is_typing: Boolean(isTyping)
                })
            });
        } catch (error) {
            console.warn('Typing sync failed:', error);
        }
    }

    _startTypingPolling(userId, dialogId, peerConnectycubeId) {
        this._stopTypingPolling(userId);

        const poll = async () => {
            try {
                const query = new URLSearchParams({
                    dialog_id: dialogId,
                    peer_connectycube_id: String(peerConnectycubeId)
                });

                const response = await fetch(`/api/chat/typing?${query.toString()}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) return;
                const payload = await response.json();
                this._updateTypingIndicatorInWindow(userId, Boolean(payload.is_typing));
            } catch (error) {
                console.warn('Typing polling failed:', error);
            }
        };

        poll();
        const intervalId = setInterval(poll, 2000);
        this.typingPollers.set(userId, intervalId);
    }

    _stopTypingPolling(userId) {
        const intervalId = this.typingPollers.get(userId);
        if (intervalId) {
            clearInterval(intervalId);
            this.typingPollers.delete(userId);
        }
    }

    _focusChatWindow(userId) {
        const chatWindow = document.getElementById(`chat-window-${userId}`);
        if (chatWindow) {
            chatWindow.style.zIndex = '10001';
            const input = chatWindow.querySelector('.chat-message-input');
            if (input) input.focus();

            chatWindow.classList.remove('has-new-message');
        }
    }

    _closeChatWindow(userId) {
        const chatWindow = document.getElementById(`chat-window-${userId}`);
        if (chatWindow) {
            chatWindow.remove();
            this._stopTypingPolling(userId);
            this.openChatWindows.delete(userId);
            this._positionChatWindows();
            console.log(`Closed chat window for user ${userId}. Remaining: ${this.openChatWindows.size}`);
        }
    }

    _flashWindowTitle(userId) {
        const chatWindow = document.getElementById(`chat-window-${userId}`);
        if (chatWindow) {
            chatWindow.classList.add('has-new-message');
            setTimeout(() => {
                chatWindow.classList.remove('has-new-message');
            }, 3000);
        }
    }

    _showMessageNotification(user, messageText) {
        const event = new CustomEvent('newNavbarMessage', {
            detail: {
                user: user,
                message: messageText
            }
        });
        document.dispatchEvent(event);

        if (Notification.permission === 'granted') {
            const userName = user.firstname || user.first_name || user.name || 'Unknown';
            new Notification(userName, {
                body: messageText,
                icon: '/path/to/chat-icon.png',
                tag: `chat-${user.id}`
            });
        } else if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    _makeDraggable(element) {
        let isDragging = false;
        let currentX;
        let currentY;
        let initialX;
        let initialY;
        let xOffset = 0;
        let yOffset = 0;

        const header = element.querySelector('.chat-window-header');
        if (!header) return;

        header.addEventListener('mousedown', (e) => {
            initialX = e.clientX - xOffset;
            initialY = e.clientY - yOffset;

            if (e.target === header || header.contains(e.target)) {
                isDragging = true;
            }
        });

        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                e.preventDefault();
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;

                xOffset = currentX;
                yOffset = currentY;

                element.style.transform = `translate3d(${currentX}px, ${currentY}px, 0)`;
            }
        });

        document.addEventListener('mouseup', () => {
            initialX = currentX;
            initialY = currentY;
            isDragging = false;
        });
    }

    async _renderUsersList() {
        const container = document.getElementById('users-list');
        if (!container) return;

        container.innerHTML = '';
        const currentUser = @json(auth()->user());

        this.allUsers.forEach(user => {
            if (user.id === currentUser.id) return;

            const isOnline = this.onlineStatusMap.get(user.connectycube_id) || false;
            const userName = user.firstname || user.first_name || user.name || 'Unknown';
            const userLastName = user.lastname || user.last_name || '';

            const userEl = document.createElement('div');
            userEl.className = 'user-item';
            userEl.setAttribute('data-theme', this.currentTheme);
            userEl.innerHTML = `
                <div class="user-avatar">
                    <div class="avatar-circle ${isOnline ? 'online' : 'offline'}">
                        ${userName.charAt(0).toUpperCase()}
                    </div>
                    <div class="online-indicator ${isOnline ? 'online' : ''}"></div>
                </div>
                <div class="user-info">
                    <div class="user-name">${userName} ${userLastName}</div>
                    <div class="user-meta">
                        <span class="user-role">${user.role_type || 'User'}</span>
                        <span class="user-status ${isOnline ? 'online' : 'offline'}">${isOnline ? 'Online' : 'Offline'}</span>
                    </div>
                </div>                                                     
            `;

            userEl.addEventListener('click', () => this._openChatWindow(user));
            container.appendChild(userEl);
        });

        if (this.allUsers.length === 0) {
            container.innerHTML = '<div class="no-users" data-theme="' + this.currentTheme + '">No users available</div>';
        }
    }

    async _getOrCreateDialog(user) {
        const cacheKey = `dialog_${this.currentUser.id}_${user.id}`;

        if (this.activeDialogs.has(cacheKey)) {
            return this.activeDialogs.get(cacheKey);
        }

        try {
            const dialogs = await ConnectyCube.chat.dialog.list({
                type: 3,
                limit: 100
            });

            const existingDialog = dialogs.items.find(dialog => {
                return dialog.type === 3 &&
                    dialog.occupants_ids.includes(this.currentUser.id) &&
                    dialog.occupants_ids.includes(user.connectycube_id);
            });

            if (existingDialog) {
                this.activeDialogs.set(cacheKey, existingDialog);
                return existingDialog;
            }

            const userName = user.firstname || user.first_name || user.name || 'User';
            const newDialog = await ConnectyCube.chat.dialog.create({
                type: 3,
                occupants_ids: [this.currentUser.id, user.connectycube_id],
                name: `Chat: ${this.currentUser.login} & ${userName}`,
                custom_data: JSON.stringify({
                    user1_id: this.currentUser.id,
                    user2_id: user.id,
                    user1_role: this.currentUser.role_type || 'user',
                    user2_role: user.role_type || 'user',
                    created_at: new Date().toISOString()
                })
            });

            console.log('Created new dialog:', newDialog);
            this.activeDialogs.set(cacheKey, newDialog);
            return newDialog;

        } catch (error) {
            console.error('Dialog creation failed:', error);
            throw new Error(`Could not create dialog: ${error.message}`);
        }
    }

    _updateOnlineStatus() {
        if (this.currentView === 'users' && this.isMainChatCreated) {
            this._renderUsersList();
        }
    }

    _refreshOnlineStatus() {
        this.allUsers.forEach(user => {
            if (user.connectycube_id && user.connectycube_id !== this.currentUser.id) {
                try {
                    ConnectyCube.chat.roster.get(user.connectycube_id, (roster) => {
                        if (roster && roster.subscription === 'both') {
                            this.onlineUsers.add(user.connectycube_id);
                            this.onlineStatusMap.set(user.connectycube_id, true);
                        }
                    });
                } catch (error) {
                    console.log('Could not get presence for user:', user.connectycube_id);
                }
            }
        });
        this._updateOnlineStatus();
    }

    // Add status update methods
    startStatusUpdates() {
        if (this.statusUpdateInterval) return; // Already running

        this.statusUpdateInterval = setInterval(async () => {
            try {
                await this.fetchUsersStatus();
            } catch (error) {
                console.error('Status update failed:', error);
            }
        }, 30000); // Update every 30 seconds
    }

    stopStatusUpdates() {
        if (this.statusUpdateInterval) {
            clearInterval(this.statusUpdateInterval);
            this.statusUpdateInterval = null;
        }
    }

    _removeExistingUI() {
        const existing = document.getElementById('connectycube-chat-container');
        if (existing) existing.remove();

        const launcher = document.getElementById('chat-launcher');
        if (launcher) launcher.remove();

        document.querySelectorAll('.individual-chat-window').forEach(window => {
            window.remove();
        });
    }

    _showError(message) {
        const existingError = document.querySelector('.chat-error');
        if (existingError) existingError.remove();

        const errorEl = document.createElement('div');
        errorEl.className = 'chat-error';
        errorEl.setAttribute('data-theme', this.currentTheme);
        errorEl.innerHTML = `
            <div class="error-content">
                <span class="error-message">${this._escapeHtml(message)}</span>
                <button class="error-close" aria-label="Close error">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(errorEl);

        errorEl.querySelector('.error-close').addEventListener('click', () => {
            errorEl.remove();
        });

        setTimeout(() => {
            if (errorEl.parentNode) {
                errorEl.remove();
            }
        }, 5000);
    }

    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Enhanced cleanup method
    destroy() {
        this.stopStatusUpdates();

        if (this.presenceUpdateInterval) {
            clearInterval(this.presenceUpdateInterval);
        }

        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        this.openChatWindows.forEach((_, userId) => {
            this._closeChatWindow(userId);
        });

        this.typingPollers.forEach((intervalId) => clearInterval(intervalId));
        this.typingPollers.clear();

        this._removeExistingUI();

        if (window.ConnectyCube && ConnectyCube.chat) {
            try {
                ConnectyCube.chat.disconnect();
            } catch (error) {
                console.error('Error disconnecting from chat:', error);
            }
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const currentUser = @json(auth()->user());
    if (!currentUser?.id) {
        console.error('No authenticated user found');
        return;
    }

    // Create global instance
    window.chatManager = new ConnectyCubeChatManager();

    // Auto-initialize
    window.chatManager.initialize().catch(error => {
        console.error('Failed to initialize chat:', error);
    });
});

// Global functions
window.hideConnectyCubeChat = () => {
    if (window.chatManager) {
        window.chatManager.hideChat();
    }
};

window.openChatWithUser = (userId) => {
    if (window.chatManager && window.chatManager.isInitialized) {
        const user = window.chatManager.allUsers.find(u => u.id === userId);
        if (user) {
            window.chatManager._openChatWindow(user);
        }
    }
};
</script>
