<template>
	<div id="localllm-app">
		<aside class="sidebar" :class="{ closed: sidebarClosed }">
			<button
				class="floating-toggle"
				v-if="sidebarClosed"
				@click="toggleSidebar"
				:title="'Open navigation'"
			>
				<span class="hamburger-icon">☰</span>
			</button>

			<div class="sidebar-header">
				<div class="sidebar-header-top">
					<button class="new-chat-button" @click="createNewConversation">
						<span class="icon">+</span> New Conversation
					</button>
					<button class="sidebar-toggle" @click="toggleSidebar" :title="'Close navigation'">
						<span class="hamburger-icon">☰</span>
					</button>
				</div>
			</div>

			<div class="conversations-list">
				<div
					v-for="conversation in conversations"
					:key="conversation.id"
					class="conversation-item"
					:class="{ active: currentConversationId === conversation.id }"
					@click="selectConversation(conversation.id)"
				>
					<span class="conversation-name">{{ conversation.name }}</span>
					<button class="delete-button" @click.stop="deleteConversation(conversation.id)" title="Delete conversation">
						×
					</button>
				</div>
			</div>

			<div class="sidebar-footer">
				<button class="settings-button" @click="toggleSettings">
					<span class="icon">⚙</span> Settings
				</button>
				<div v-if="showSettings" class="settings-panel">
					<ConfigSettings @config-updated="loadConfigs" />
				</div>
			</div>
		</aside>

		<main class="main-content">
			<ChatWindow
				v-if="currentConversationId !== null || isNewConversation"
				:conversation-id="currentConversationId"
				:is-new="isNewConversation"
				@message-sent="handleMessageSent"
			/>
			<div v-else class="empty-content">
				<div class="empty-icon">💬</div>
				<h2>Start a conversation</h2>
				<p>Click New Conversation to get started</p>
			</div>
		</main>
	</div>
</template>

<script>
import ChatWindow from './components/ChatWindow.vue'
import ConfigSettings from './components/ConfigSettings.vue'
import { getConversations, deleteConversation as apiDeleteConversation } from './services/api'

export default {
	name: 'App',
	components: {
		ChatWindow,
		ConfigSettings,
	},
	data() {
		return {
			conversations: [],
			currentConversationId: null,
			isNewConversation: false,
			sidebarClosed: false,
			showSettings: false,
		}
	},
	mounted() {
		this.loadConversations()
	},
	methods: {
		async loadConversations() {
			try {
				this.conversations = await getConversations()
			} catch (error) {
				console.error('Failed to load conversations:', error)
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Failed to load conversations'))
			}
		},
		createNewConversation() {
			this.currentConversationId = null
			this.isNewConversation = true
		},
		selectConversation(id) {
			this.currentConversationId = id
			this.isNewConversation = false
		},
		async deleteConversation(id) {
			if (!confirm(t('local-llm-nextcloud', 'Are you sure you want to delete this conversation?'))) {
				return
			}

			try {
				await apiDeleteConversation(id)
				await this.loadConversations()
				if (this.currentConversationId === id) {
					this.currentConversationId = null
					this.isNewConversation = false
				}
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Conversation deleted'))
			} catch (error) {
				console.error('Failed to delete conversation:', error)
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Failed to delete conversation'))
			}
		},
		handleMessageSent(conversationId) {
			if (this.isNewConversation) {
				this.currentConversationId = conversationId
				this.isNewConversation = false
				this.loadConversations()
			}
		},
		loadConfigs() {
			// Trigger reload if needed
		},
		toggleSidebar() {
			this.sidebarClosed = !this.sidebarClosed
		},
		toggleSettings() {
			this.showSettings = !this.showSettings
		},
	},
}
</script>

<style scoped>
#localllm-app {
	width: 100%;
	height: 100%;
	display: flex;
	background: var(--color-main-background);
}

/* Sidebar */
.sidebar {
	width: 420px;
	min-width: 420px;
	height: 100%;
	background: var(--color-main-background);
	border-right: 1px solid rgba(255, 255, 255, 0.1);
	box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
	display: flex;
	flex-direction: column;
	transition: all 0.3s ease;
	position: relative;
}

.sidebar.closed {
	width: 66px;
	min-width: 66px;
}

.sidebar.closed .sidebar-header,
.sidebar.closed .conversations-list,
.sidebar.closed .sidebar-footer {
	display: none;
}

/* Sidebar Header */
.sidebar-header {
	padding: 12px;
}

.sidebar-header-top {
	display: flex;
	gap: 8px;
	position: relative;
}

/* New Chat Button */
.new-chat-button {
	flex: 1;
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 16px;
	background: transparent;
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 8px;
	color: var(--color-main-text);
	cursor: pointer;
	transition: all 0.2s ease;
	font-size: 14px;
	font-weight: 500;
}

.new-chat-button:hover {
	background: rgba(255, 255, 255, 0.05);
	border-color: rgba(255, 255, 255, 0.1);
}

.new-chat-button .icon {
	font-size: 18px;
	font-weight: bold;
}

/* Sidebar Toggle */
.sidebar-toggle {
	width: 50px;
	height: 50px;
	padding: 0;
	background: transparent;
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 8px;
	color: var(--color-main-text);
	cursor: pointer;
	transition: all 0.2s ease;
	display: flex;
	align-items: center;
	justify-content: center;
}

.sidebar-toggle:hover {
	background: rgba(255, 255, 255, 0.05);
	border-color: rgba(255, 255, 255, 0.1);
}

/* Floating Toggle (shown when sidebar is closed) */
.floating-toggle {
	position: absolute;
	top: 12px;
	left: 8px;
	width: 50px;
	height: 50px;
	padding: 0;
	background: var(--color-main-background);
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 8px;
	color: var(--color-main-text);
	cursor: pointer;
	transition: all 0.2s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 1000;
}

.floating-toggle:hover {
	background: rgba(255, 255, 255, 0.05);
	border-color: rgba(255, 255, 255, 0.1);
}

.hamburger-icon {
	font-size: 20px;
	opacity: 0.7;
}

/* Conversations List */
.conversations-list {
	flex: 1;
	overflow-y: auto;
	padding: 0 12px;
	border-top: 1px solid rgba(255, 255, 255, 0.1);
	padding-top: 12px;
}

.conversation-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 16px;
	margin-bottom: 8px;
	background: transparent;
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.2s ease;
}

.conversation-item:hover {
	background: rgba(255, 255, 255, 0.05);
	border-color: rgba(255, 255, 255, 0.1);
}

.conversation-item.active {
	background: rgba(255, 255, 255, 0.08);
	border-color: rgba(255, 255, 255, 0.1);
}

.conversation-name {
	flex: 1;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	color: var(--color-main-text);
	font-size: 14px;
}

.delete-button {
	width: 24px;
	height: 24px;
	padding: 0;
	background: transparent;
	border: none;
	color: var(--color-main-text);
	cursor: pointer;
	opacity: 0;
	transition: opacity 0.2s ease;
	font-size: 20px;
	line-height: 1;
}

.conversation-item:hover .delete-button {
	opacity: 0.6;
}

.delete-button:hover {
	opacity: 1 !important;
	color: var(--color-error);
}

/* Sidebar Footer */
.sidebar-footer {
	padding: 12px;
	border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.settings-button {
	width: 100%;
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 16px;
	background: transparent;
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 8px;
	color: var(--color-main-text);
	cursor: pointer;
	transition: all 0.2s ease;
	font-size: 14px;
}

.settings-button:hover {
	background: rgba(255, 255, 255, 0.05);
	border-color: rgba(255, 255, 255, 0.1);
}

.settings-button .icon {
	font-size: 16px;
}

.settings-panel {
	margin-top: 12px;
	padding: 16px;
	background: rgba(255, 255, 255, 0.03);
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 8px;
}

/* Main Content */
.main-content {
	flex: 1;
	height: 100%;
	overflow: hidden;
}

/* Empty Content */
.empty-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
	color: var(--color-text-lighter);
}

.empty-icon {
	font-size: 64px;
	margin-bottom: 16px;
	opacity: 0.5;
}

.empty-content h2 {
	margin: 0 0 8px 0;
	font-size: 24px;
	font-weight: 500;
}

.empty-content p {
	margin: 0;
	font-size: 14px;
	opacity: 0.7;
}
</style>
