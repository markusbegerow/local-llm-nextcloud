<template>
	<div class="chat-window">
		<div v-if="isNew && !conversationId" class="model-selector">
			<label>{{ t('local-llm-nextcloud', 'Choose Model:') }}</label>
			<select v-model="selectedConfigId">
				<option v-for="config in availableConfigs" :key="config.id" :value="config.id">
					{{ config.name }} - {{ config.modelName }}
				</option>
			</select>
		</div>

		<div class="messages-container" ref="messagesContainer">
			<div v-if="loading" class="loading">
				<span class="icon-loading" />
				{{ t('local-llm-nextcloud', 'Loading messages...') }}
			</div>
			<div v-else-if="messages.length === 0" class="empty-messages">
				<p>{{ t('local-llm-nextcloud', 'No messages yet. Start the conversation!') }}</p>
			</div>
			<div v-else>
				<div
					v-for="message in messages"
					:key="message.id"
					:class="['message', `message-${message.role}`]"
				>
					<div class="message-header">
						<span class="message-role">
							{{ message.role === 'user' ? t('local-llm-nextcloud', 'You') : t('local-llm-nextcloud', 'Assistant') }}
						</span>
						<span class="message-time">
							{{ formatTime(message.createdAt) }}
						</span>
					</div>
					<div class="message-content">
						{{ message.content }}
					</div>
				</div>
			</div>
		</div>

		<div class="input-container">
			<textarea
				v-model="currentMessage"
				:placeholder="t('local-llm-nextcloud', 'Type your message...')"
				:disabled="sending"
				@keydown.enter.exact.prevent="sendMessage"
				@keydown.enter.shift.exact="addNewLine"
				rows="3"
			/>
			<button
				:disabled="!currentMessage.trim() || sending"
				@click="sendMessage"
				class="primary"
			>
				<span v-if="sending" class="icon-loading-small" />
				<span v-else class="icon-confirm" />
				{{ sending ? t('local-llm-nextcloud', 'Sending...') : t('local-llm-nextcloud', 'Send') }}
			</button>
		</div>
	</div>
</template>

<script>
import { sendMessage as apiSendMessage, getMessages, getConfigs } from '../services/api'

export default {
	name: 'ChatWindow',
	props: {
		conversationId: {
			type: Number,
			default: null,
		},
		isNew: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			messages: [],
			currentMessage: '',
			sending: false,
			loading: false,
			availableConfigs: [],
			selectedConfigId: null,
		}
	},
	mounted() {
		this.loadConfigs()
	},
	watch: {
		conversationId: {
			immediate: true,
			handler(newId) {
				if (newId) {
					this.loadMessages()
				} else {
					this.messages = []
				}
			},
		},
	},
	methods: {
		async loadConfigs() {
			try {
				this.availableConfigs = await getConfigs()
				// Set default config as selected
				const defaultConfig = this.availableConfigs.find(c => c.isDefault)
				if (defaultConfig) {
					this.selectedConfigId = defaultConfig.id
				} else if (this.availableConfigs.length > 0) {
					this.selectedConfigId = this.availableConfigs[0].id
				}
			} catch (error) {
				console.error('Failed to load configs:', error)
			}
		},
		async loadMessages() {
			if (!this.conversationId) {
				return
			}

			this.loading = true
			try {
				this.messages = await getMessages(this.conversationId)
				this.$nextTick(() => {
					this.scrollToBottom()
				})
			} catch (error) {
				console.error('Failed to load messages:', error)
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Failed to load messages'))
			} finally {
				this.loading = false
			}
		},
		async sendMessage() {
			if (!this.currentMessage.trim() || this.sending) {
				return
			}

			const messageText = this.currentMessage.trim()
			this.currentMessage = ''
			this.sending = true

			try {
				// Pass the selected config ID for new conversations
				const configId = (this.isNew && !this.conversationId) ? this.selectedConfigId : null
				const response = await apiSendMessage(this.conversationId, messageText, configId)

				// Add messages to local state
				this.messages.push(response.userMessage)
				this.messages.push(response.assistantMessage)

				// If this was a new conversation, emit the conversation ID
				if (this.isNew || !this.conversationId) {
					this.$emit('message-sent', response.conversationId)
				}

				this.$nextTick(() => {
					this.scrollToBottom()
				})
			} catch (error) {
				console.error('Failed to send message:', error)
				console.error('Error response:', error.response)
				let errorMsg = t('local-llm-nextcloud', 'Failed to send message')
				if (error.response?.data?.error) {
					errorMsg = error.response.data.error
				} else if (error.response?.data) {
					errorMsg = JSON.stringify(error.response.data)
				} else if (error.message) {
					errorMsg = error.message
				}
				OC.Notification.showTemporary(errorMsg)
			} finally {
				this.sending = false
			}
		},
		addNewLine() {
			this.currentMessage += '\n'
		},
		scrollToBottom() {
			const container = this.$refs.messagesContainer
			if (container) {
				container.scrollTop = container.scrollHeight
			}
		},
		formatTime(timestamp) {
			if (!timestamp) return ''
			const date = new Date(timestamp * 1000)
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
		},
	},
}
</script>

<style scoped>
.chat-window {
	display: flex;
	flex-direction: column;
	width: 100%;
	height: 100%;
	padding: 20px 20px 0 20px;
	box-sizing: border-box;
	max-width: 100%;
	overflow: hidden;
}

.model-selector {
	margin-bottom: 15px;
	padding: 15px;
	background-color: var(--color-background-dark);
	border-radius: 8px;
	border: 1px solid var(--color-border);
}

.model-selector label {
	display: block;
	margin-bottom: 8px;
	font-weight: bold;
	color: var(--color-text-light);
}

.model-selector select {
	width: 100%;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
	background-color: var(--color-main-background);
	color: var(--color-text-dark);
}

.messages-container {
	flex: 1;
	overflow-y: auto;
	margin-bottom: 20px;
	padding: 10px;
	background-color: var(--color-main-background);
	border-radius: 8px;
	min-height: 0;
	max-height: calc(100% - 120px);
}

.loading,
.empty-messages {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 100%;
	color: var(--color-text-lighter);
}

.message {
	margin-bottom: 20px;
	padding: 12px;
	border-radius: 8px;
}

.message-user {
	background-color: var(--color-primary-light);
	margin-left: 20%;
}

.message-assistant {
	background-color: var(--color-background-dark);
	margin-right: 20%;
}

.message-header {
	display: flex;
	justify-content: space-between;
	margin-bottom: 8px;
	font-size: 0.9em;
	opacity: 0.8;
}

.message-role {
	font-weight: bold;
}

.message-content {
	white-space: pre-wrap;
	word-wrap: break-word;
}

.input-container {
	display: flex;
	gap: 10px;
	flex-shrink: 0;
	width: 100%;
	max-width: 100%;
	box-sizing: border-box;
}

.input-container textarea {
	flex: 1;
	padding: 10px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
	resize: vertical;
	font-family: inherit;
	max-width: 100%;
	box-sizing: border-box;
}

.input-container button {
	padding: 10px 20px;
	white-space: nowrap;
	flex-shrink: 0;
}
</style>
