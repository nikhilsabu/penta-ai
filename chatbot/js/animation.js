window.ChatAnimation = {
  showTyping(container) {
    const node = document.createElement("div");
    node.className = "message message-ai typing-message";
    node.setAttribute("data-typing", "true");
    node.innerHTML =
      '<div class="typing-indicator" aria-label="Assistant is typing"><span></span><span></span><span></span></div>';
    container.appendChild(node);
    container.scrollTop = container.scrollHeight;
    return node;
  },

  removeTyping(container) {
    const node = container.querySelector("[data-typing='true']");
    if (node) {
      node.remove();
    }
  },

  fadeIn(node) {
    node.style.opacity = "0";
    node.style.transform = "translateY(8px)";
    requestAnimationFrame(() => {
      node.style.transition = "opacity .24s ease, transform .24s ease";
      node.style.opacity = "1";
      node.style.transform = "translateY(0)";
    });
  },
};
