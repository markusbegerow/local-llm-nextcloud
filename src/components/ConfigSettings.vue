<template>
	<div class="config-settings">
		<h3>{{ t('local-llm-nextcloud', 'LLM Configurations') }}</h3>

		<div v-if="loading" class="loading">
			<NcLoadingIcon />
		</div>

		<div v-else>
			<div v-for="config in configs" :key="config.id" class="config-item">
				<div class="config-header">
					<h4>
						{{ config.name }}
						<span v-if="config.isDefault" class="badge">
							{{ t('local-llm-nextcloud', 'Default') }}
						</span>
					</h4>
					<div class="config-actions">
						<NcActions v-if="!config.isDefault">
							<NcActionButton
								@click="setDefault(config.id)"
								:aria-label="t('local-llm-nextcloud', 'Set as default')"
							>
								<template #icon>
									<Star :size="20" />
								</template>
								{{ t('local-llm-nextcloud', 'Set as default') }}
							</NcActionButton>
						</NcActions>
						<NcActions>
							<NcActionButton
								@click="editConfig(config)"
								:aria-label="t('local-llm-nextcloud', 'Edit')"
							>
								<template #icon>
									<Pencil :size="20" />
								</template>
								{{ t('local-llm-nextcloud', 'Edit') }}
							</NcActionButton>
						</NcActions>
						<NcActions>
							<NcActionButton
								@click="testConfig(config.id)"
								:aria-label="t('local-llm-nextcloud', 'Test connection')"
							>
								<template #icon>
									<Check :size="20" />
								</template>
								{{ t('local-llm-nextcloud', 'Test connection') }}
							</NcActionButton>
						</NcActions>
						<NcActions>
							<NcActionButton
								@click="deleteConfigItem(config.id)"
								:aria-label="t('local-llm-nextcloud', 'Delete')"
							>
								<template #icon>
									<Delete :size="20" />
								</template>
								{{ t('local-llm-nextcloud', 'Delete') }}
							</NcActionButton>
						</NcActions>
					</div>
				</div>
				<div class="config-details">
					<p><strong>{{ t('local-llm-nextcloud', 'API URL') }}:</strong> {{ config.apiUrl }}</p>
					<p><strong>{{ t('local-llm-nextcloud', 'Model') }}:</strong> {{ config.modelName }}</p>
					<p><strong>{{ t('local-llm-nextcloud', 'Temperature') }}:</strong> {{ config.temperature }}</p>
				</div>
			</div>

			<NcButton
				type="primary"
				@click="showNewConfigForm = !showNewConfigForm"
			>
				<template #icon>
					<Plus :size="20" />
				</template>
				{{ t('local-llm-nextcloud', 'Add Configuration') }}
			</NcButton>

			<div v-if="showNewConfigForm" class="new-config-form">
				<h4>{{ editingConfig ? t('local-llm-nextcloud', 'Edit Configuration') : t('local-llm-nextcloud', 'New Configuration') }}</h4>
				<div class="form-group">
					<label>{{ t('local-llm-nextcloud', 'Name') }}</label>
					<input v-model="newConfig.name" type="text" required />
				</div>
				<div class="form-group">
					<label>{{ t('local-llm-nextcloud', 'API URL') }}</label>
					<input
						v-model="newConfig.apiUrl"
						type="text"
						placeholder="http://localhost:11434/v1/chat/completions"
						required
					/>
				</div>
				<div class="form-group">
					<label>{{ t('local-llm-nextcloud', 'API Token') }}</label>
					<input
						v-model="newConfig.apiToken"
						type="password"
						placeholder="ollama"
						required
					/>
				</div>
				<div class="form-group">
					<label>{{ t('local-llm-nextcloud', 'Model Name') }}</label>
					<input
						v-model="newConfig.modelName"
						type="text"
						placeholder="llama3.2"
						required
					/>
				</div>
				<div class="form-group">
					<label>{{ t('local-llm-nextcloud', 'Temperature') }} (0-2)</label>
					<input
						v-model.number="newConfig.temperature"
						type="number"
						min="0"
						max="2"
						step="0.1"
					/>
				</div>
				<div class="form-group">
					<label>{{ t('local-llm-nextcloud', 'Max Tokens') }}</label>
					<input
						v-model.number="newConfig.maxTokens"
						type="number"
						min="128"
						max="32768"
					/>
				</div>
				<div class="form-group">
					<label>{{ t('local-llm-nextcloud', 'System Prompt') }}</label>
					<textarea v-model="newConfig.systemPrompt" rows="3" />
				</div>
				<div class="form-actions">
					<NcButton type="primary" @click="saveConfig">
						{{ editingConfig ? t('local-llm-nextcloud', 'Update') : t('local-llm-nextcloud', 'Save') }}
					</NcButton>
					<NcButton @click="cancelConfigForm">
						{{ t('local-llm-nextcloud', 'Cancel') }}
					</NcButton>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Plus from 'vue-material-design-icons/Plus.vue'
import Star from 'vue-material-design-icons/Star.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Check from 'vue-material-design-icons/Check.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import {
	getConfigs,
	createConfig,
	updateConfig,
	deleteConfig,
	testConnection,
	setDefaultConfig,
} from '../services/api'

export default {
	name: 'ConfigSettings',
	components: {
		NcButton,
		NcActions,
		NcActionButton,
		NcLoadingIcon,
		Plus,
		Star,
		Pencil,
		Check,
		Delete,
	},
	data() {
		return {
			configs: [],
			loading: false,
			showNewConfigForm: false,
			editingConfig: null,
			newConfig: this.getEmptyConfig(),
		}
	},
	mounted() {
		this.loadConfigs()
	},
	methods: {
		async loadConfigs() {
			this.loading = true
			try {
				this.configs = await getConfigs()
			} catch (error) {
				console.error('Failed to load configs:', error)
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Failed to load configurations'))
			} finally {
				this.loading = false
			}
		},
		getEmptyConfig() {
			return {
				name: '',
				apiUrl: 'http://localhost:11434/v1/chat/completions',
				apiToken: 'ollama',
				modelName: 'llama3.2',
				temperature: 0.7,
				maxTokens: 2048,
				systemPrompt: 'You are a helpful AI assistant integrated into Nextcloud. Help users with their tasks, answer questions, and provide insights. Keep responses clear, concise, and professional.',
			}
		},
		editConfig(config) {
			this.editingConfig = config.id
			this.newConfig = {
				name: config.name,
				apiUrl: config.apiUrl,
				apiToken: '', // Don't pre-fill token for security
				modelName: config.modelName,
				temperature: config.temperature,
				maxTokens: config.maxTokens,
				systemPrompt: config.systemPrompt,
			}
			this.showNewConfigForm = true
		},
		async saveConfig() {
			try {
				if (this.editingConfig) {
					// Update existing config
					await updateConfig(this.editingConfig, this.newConfig)
					OC.Notification.showTemporary(t('local-llm-nextcloud', 'Configuration updated'))
				} else {
					// Create new config
					await createConfig(this.newConfig)
					OC.Notification.showTemporary(t('local-llm-nextcloud', 'Configuration created'))
				}
				await this.loadConfigs()
				this.cancelConfigForm()
				this.$emit('config-updated')
			} catch (error) {
				console.error('Failed to save config:', error)
				OC.Notification.showTemporary(t('local-llm-nextcloud', this.editingConfig ? 'Failed to update configuration' : 'Failed to create configuration'))
			}
		},
		cancelConfigForm() {
			this.showNewConfigForm = false
			this.editingConfig = null
			this.newConfig = this.getEmptyConfig()
		},
		async deleteConfigItem(id) {
			if (!confirm(t('local-llm-nextcloud', 'Are you sure you want to delete this configuration?'))) {
				return
			}

			try {
				await deleteConfig(id)
				await this.loadConfigs()
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Configuration deleted'))
				this.$emit('config-updated')
			} catch (error) {
				console.error('Failed to delete config:', error)
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Failed to delete configuration'))
			}
		},
		async testConfig(id) {
			try {
				const result = await testConnection(id)
				if (result.success) {
					OC.Notification.showTemporary(t('local-llm-nextcloud', 'Connection successful!'))
				} else {
					OC.Notification.showTemporary(result.message)
				}
			} catch (error) {
				console.error('Failed to test connection:', error)
				const errorMsg = error.response?.data?.message || t('local-llm-nextcloud', 'Connection failed')
				OC.Notification.showTemporary(errorMsg)
			}
		},
		async setDefault(id) {
			try {
				await setDefaultConfig(id)
				await this.loadConfigs()
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Default configuration updated'))
				this.$emit('config-updated')
			} catch (error) {
				console.error('Failed to set default:', error)
				OC.Notification.showTemporary(t('local-llm-nextcloud', 'Failed to update default configuration'))
			}
		},
	},
}
</script>

<style scoped>
.config-settings {
	padding: 20px;
}

.loading {
	display: flex;
	justify-content: center;
	padding: 20px;
}

.config-item {
	margin-bottom: 20px;
	padding: 15px;
	border: 1px solid var(--color-border);
	border-radius: 8px;
	overflow: visible;
}

.config-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 10px;
	gap: 10px;
}

.config-header h4 {
	margin: 0;
	display: flex;
	align-items: center;
	gap: 10px;
	flex: 1;
	min-width: 0;
	word-wrap: break-word;
}

.badge {
	background-color: var(--color-primary);
	color: white;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.8em;
}

.config-actions {
	display: flex;
	gap: 4px;
	flex-shrink: 0;
	align-items: center;
}

.config-details p {
	margin: 5px 0;
	font-size: 0.9em;
}

.new-config-form {
	margin-top: 20px;
	padding: 20px;
	border: 1px solid var(--color-border);
	border-radius: 8px;
	background-color: var(--color-background-dark);
}

.form-group {
	margin-bottom: 15px;
}

.form-group label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
}

.form-group input,
.form-group textarea {
	width: 100%;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
}

.form-actions {
	display: flex;
	gap: 10px;
	margin-top: 15px;
}
</style>
