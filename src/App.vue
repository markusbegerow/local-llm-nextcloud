<template>
	<div id="localllm-app">
		<NcAppNavigation>
			<template #list>
				<NcAppNavigationItem
					:title="t('local-llm-nextcloud', 'New Conversation')"
					icon="icon-add"
					@click="createNewConversation"
				/>
				<NcAppNavigationItem
					v-for="conversation in conversations"
					:key="conversation.id"
					:name="conversation.name"
					:title="conversation.name"
					:class="{ active: currentConversationId === conversation.id }"
					@click="selectConversation(conversation.id)"
				>
					<template #actions>
						<NcActionButton icon="icon-delete" @click="deleteConversation(conversation.id)">
							{{ t('local-llm-nextcloud', 'Delete') }}
						</NcActionButton>
					</template>
				</NcAppNavigationItem>
			</template>
			<template #footer>
				<NcAppNavigationSettings :title="t('local-llm-nextcloud', 'Settings')">
					<ConfigSettings @config-updated="loadConfigs" />
				</NcAppNavigationSettings>
			</template>
		</NcAppNavigation>

		<NcContent app-name="local-llm">
			<ChatWindow
				v-if="currentConversationId !== null || isNewConversation"
				:conversation-id="currentConversationId"
				:is-new="isNewConversation"
				@message-sent="handleMessageSent"
			/>
			<NcEmptyContent
				v-else
				icon="icon-comment"
				:title="t('local-llm-nextcloud', 'Start a conversation')"
				:description="t('local-llm-nextcloud', 'Click New Conversation to get started')"
			/>
		</NcContent>
	</div>
</template>

<script>
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationSettings from '@nextcloud/vue/dist/Components/NcAppNavigationSettings.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import ChatWindow from './components/ChatWindow.vue'
import ConfigSettings from './components/ConfigSettings.vue'
import { getConversations, deleteConversation as apiDeleteConversation } from './services/api'

export default {
	name: 'App',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationSettings,
		NcContent,
		NcActionButton,
		NcEmptyContent,
		ChatWindow,
		ConfigSettings,
	},
	data() {
		return {
			conversations: [],
			currentConversationId: null,
			isNewConversation: false,
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
	},
}
</script>

<style scoped>
#localllm-app {
	width: 100%;
	height: 100%;
	display: flex;
}

.active {
	background-color: var(--color-primary-light);
}
</style>

<style>
/* Override NcContent fixed positioning */
#content-vue.app-local-llm {
	position: relative !important;
}

/* Make navigation sidebar wider when open */
#localllm-app .app-navigation:not(.app-navigation--close) {
	width: 420px !important;
	max-width: 420px !important;
}
</style>
