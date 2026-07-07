(function () {
  const isGitHubPages =
    location.hostname.endsWith("github.io") ||
    location.hostname.endsWith("githubusercontent.com");

  if (isGitHubPages) {
    window.PENTAME_CHATBOT_CONFIG = {
      apiKey: "",
      apiBase: "",
      uploadBase: "",
      isStaticDemo: true,
    };
    return;
  }

  document.write('<script src="php/bootstrap.js.php"><\/script>');
})();
