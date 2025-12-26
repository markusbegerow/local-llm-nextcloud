import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Send a chat message
 */
export async function sendMessage(conversationId, message, configId = null) {
	const response = await axios.post(generateUrl('/apps/local-llm-nextcloud/api/chat'), {
		conversationId,
		message,
		configId,
	})
	return response.data
}

/**
 * Get all conversations
 */
export async function getConversations() {
	const response = await axios.get(generateUrl('/apps/local-llm-nextcloud/api/conversations'))
	return response.data
}

/**
 * Get a specific conversation
 */
export async function getConversation(id) {
	const response = await axios.get(generateUrl(`/apps/local-llm-nextcloud/api/conversations/${id}`))
	return response.data
}

/**
 * Get messages for a conversation
 */
export async function getMessages(conversationId) {
	const response = await axios.get(generateUrl(`/apps/local-llm-nextcloud/api/conversations/${conversationId}/messages`))
	return response.data
}

/**
 * Delete a conversation
 */
export async function deleteConversation(id) {
	const response = await axios.delete(generateUrl(`/apps/local-llm-nextcloud/api/conversations/${id}`))
	return response.data
}

/**
 * Clear messages in a conversation
 */
export async function clearMessages(id) {
	const response = await axios.post(generateUrl(`/apps/local-llm-nextcloud/api/conversations/${id}/clear`))
	return response.data
}

/**
 * Update conversation
 */
export async function updateConversation(id, name) {
	const response = await axios.put(generateUrl(`/apps/local-llm-nextcloud/api/conversations/${id}`), {
		name,
	})
	return response.data
}

/**
 * Get all configs
 */
export async function getConfigs() {
	const response = await axios.get(generateUrl('/apps/local-llm-nextcloud/api/configs'))
	return response.data
}

/**
 * Get a specific config
 */
export async function getConfig(id) {
	const response = await axios.get(generateUrl(`/apps/local-llm-nextcloud/api/configs/${id}`))
	return response.data
}

/**
 * Create a new config
 */
export async function createConfig(config) {
	const response = await axios.post(generateUrl('/apps/local-llm-nextcloud/api/configs'), config)
	return response.data
}

/**
 * Update a config
 */
export async function updateConfig(id, config) {
	const response = await axios.put(generateUrl(`/apps/local-llm-nextcloud/api/configs/${id}`), config)
	return response.data
}

/**
 * Delete a config
 */
export async function deleteConfig(id) {
	const response = await axios.delete(generateUrl(`/apps/local-llm-nextcloud/api/configs/${id}`))
	return response.data
}

/**
 * Test connection for a config
 */
export async function testConnection(id) {
	const response = await axios.post(generateUrl(`/apps/local-llm-nextcloud/api/configs/${id}/test`))
	return response.data
}

/**
 * Set default config
 */
export async function setDefaultConfig(id) {
	const response = await axios.post(generateUrl(`/apps/local-llm-nextcloud/api/configs/${id}/default`))
	return response.data
}
