class ChatApi {
  constructor(basePath = "php") {
    const config = window.PENTAME_CHATBOT_CONFIG || {};
    this.basePath = basePath;
    this.apiEndpoint = config.apiBase || `${basePath}/api.php`;
    this.uploadEndpoint = config.uploadBase || `${basePath}/upload-document.php`;
    this.apiKey = config.apiKey || "";
    this.sessionId = this.ensureSessionId();
  }

  ensureSessionId() {
    const key = "pentame_chat_session";
    let id = localStorage.getItem(key);
    if (!id) {
      id = `sess_${Math.random().toString(36).slice(2)}_${Date.now()}`;
      localStorage.setItem(key, id);
    }
    return id;
  }

  buildHeaders(includeJson = true) {
    const headers = {};
    if (includeJson) {
      headers["Content-Type"] = "application/json";
    }
    if (this.apiKey) {
      headers["X-API-Key"] = this.apiKey;
    }
    return headers;
  }

  async post(action, payload = {}) {
    const response = await fetch(this.apiEndpoint, {
      method: "POST",
      headers: this.buildHeaders(),
      body: JSON.stringify({
        session_id: this.sessionId,
        action,
        ...payload,
      }),
    });

    if (!response.ok) {
      const raw = await response.text();
      let parsed;
      try {
        parsed = JSON.parse(raw);
      } catch {
        throw new Error(raw || "API request failed.");
      }
      throw new Error(parsed.error || parsed.reply || "API request failed.");
    }

    return response.json();
  }

  sendMessage(message, history = [], metadata = {}) {
    return this.post("send-message", { message, history, metadata });
  }

  saveLead(leadData = {}) {
    return this.post("save-lead", leadData);
  }

  getHistory() {
    return this.post("chat-history", {});
  }

  getSettings() {
    return this.post("settings", { settings_action: "get" });
  }

  uploadDocument(file, category = "general") {
    const formData = new FormData();
    formData.append("file", file);
    formData.append("category", category);
    formData.append("session_id", this.sessionId);
    if (this.apiKey) {
      formData.append("api_key", this.apiKey);
    }

    return fetch(this.uploadEndpoint, {
      method: "POST",
      headers: this.buildHeaders(false),
      body: formData,
    }).then(async (res) => {
      const raw = await res.text();
      let parsed;
      try {
        parsed = JSON.parse(raw);
      } catch {
        throw new Error(raw || "Upload failed.");
      }
      if (!res.ok) {
        throw new Error(parsed.error || "Upload failed.");
      }
      return parsed;
    });
  }
}

window.ChatApi = ChatApi;
