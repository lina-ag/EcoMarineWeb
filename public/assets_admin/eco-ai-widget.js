document.addEventListener("DOMContentLoaded", function () {
  const widget = document.createElement("div");

  widget.innerHTML = `
    <button class="eco-ai-btn eco-ai-orb" id="ecoAiOpen" aria-label="Ouvrir EcoBot">
      <span class="eco-ai-orb-core">AI</span>
      <span class="eco-ai-orb-ring"></span>
    </button>

    <div class="eco-robot-stage" id="ecoRobotStage">
      <div class="eco-assistant-shell">
        <div class="eco-assistant-glow"></div>

        <div class="eco-assistant-header">
          <div class="eco-robot-body" id="ecoRobotBody">
            <div class="eco-robot-arm eco-robot-arm-left"></div>
            <div class="eco-robot-arm eco-robot-arm-right"></div>
            <div class="eco-robot-head">
              <div class="eco-robot-eye"></div>
              <div class="eco-robot-eye"></div>
            </div>
            <div class="eco-robot-mouth"></div>
          </div>

          <div class="eco-assistant-titlebox">
            <div class="eco-assistant-title">Assistant IA Dechets</div>
            <div class="eco-assistant-status"><span></span> pret a analyser</div>
          </div>

          <button class="eco-robot-close" id="ecoRobotClose" type="button" aria-label="Fermer">X</button>
        </div>

        <div class="eco-robot-bubble" id="ecoRobotBubble">
          Bonjour et bienvenue sur Eco Marine Dechets.
        </div>

        <div class="eco-robot-panel">
          <button class="eco-ai-small-btn eco-ai-main-action" id="ecoAiNext" type="button">Choisir une IA</button>

          <div class="eco-ai-choices" id="ecoAiChoices" style="display:none;">
            <button class="eco-ai-choice" data-ai="dechets" type="button">
              <span>Analyse image</span>
              <small>Detecter et classer les dechets</small>
            </button>
            <button class="eco-ai-choice" data-ai="nature" type="button">
              <span>Voix de la nature</span>
              <small>Transforme les constats terrain en message immersif pour sensibiliser visiteurs, equipes et citoyens.</small>
            </button>
            <button class="eco-ai-choice" data-ai="guide" type="button">
              <small class="eco-ai-choice-label">Plan terrain</small>
              <span>Guide ecologique</span>
              <small>Propose des priorites de nettoyage, tri, prevention et suivi pour organiser une intervention plus efficace.</small>
            </button>
          </div>

          <div class="eco-ai-api-box" id="ecoAiOperation">
            <strong id="ecoAiSelectedTitle">Operation IA</strong>

            <div id="ecoAiImageZone" style="display:none;">
              <label class="eco-ai-upload-card" for="ecoWasteImage">
                <span class="eco-ai-upload-icon">+</span>
                <span>
                  <strong>Ajouter une photo</strong>
                  <small>JPG, PNG ou WEBP</small>
                </span>
              </label>
              <input class="eco-ai-input" id="ecoWasteImage" type="file" accept="image/png,image/jpeg,image/webp">
              <button class="eco-ai-small-btn eco-ai-analyze-btn" id="ecoAiAnalyze" type="button">Lancer l'analyse IA</button>
            </div>

            <div class="eco-ai-result" id="ecoAiResult">
              Choisis une intelligence artificielle.
            </div>
          </div>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(widget);

  const openBtn = document.getElementById("ecoAiOpen");
  const stage = document.getElementById("ecoRobotStage");
  const closeBtn = document.getElementById("ecoRobotClose");
  const robot = document.getElementById("ecoRobotBody");
  const bubble = document.getElementById("ecoRobotBubble");
  const choices = document.getElementById("ecoAiChoices");
  const operation = document.getElementById("ecoAiOperation");
  const imageZone = document.getElementById("ecoAiImageZone");
  const title = document.getElementById("ecoAiSelectedTitle");
  const result = document.getElementById("ecoAiResult");

  let welcomeText = "Bonjour et bienvenue sur Eco Marine Dechets.\n\nJe suis votre assistant intelligent, pret a analyser les dechets marins et a vous guider vers un environnement plus propre.\n\nEnsemble, protegeons nos oceans, une action a la fois.";
  let currentText = welcomeText;
  let selectedAI = "";

  function speak(text) {
    if (!("speechSynthesis" in window)) return;

    window.speechSynthesis.cancel();

    const msg = new SpeechSynthesisUtterance(text);
    msg.lang = "fr-FR";
    msg.rate = 0.95;
    msg.pitch = 1.1;

    robot.classList.add("talking");

    msg.onend = function () {
      robot.classList.remove("talking");
    };

    window.speechSynthesis.speak(msg);
  }

  function say(text) {
    currentText = text.replace(/<[^>]*>/g, " ");
    bubble.innerHTML = text.replace(/\n/g, "<br>");
  }

  function formatAnalysis(text) {
    return text
      .split("\n")
      .filter(function (line) { return line.trim() !== ""; })
      .map(function (line) {
        return '<div class="eco-ai-result-line">' + line + '</div>';
      })
      .join("");
  }

  openBtn.onclick = function () {
    stage.classList.add("show");
    openBtn.style.display = "none";
    say(welcomeText);
    speak(welcomeText);
  };

  closeBtn.onclick = function () {
    stage.classList.remove("show");
    openBtn.style.display = "block";
    window.speechSynthesis.cancel();
  };

  document.getElementById("ecoAiNext").onclick = function () {
    choices.style.display = "flex";
    operation.classList.remove("show");
    say("Choisis maintenant une intelligence artificielle : analyse des dechets, voix de la nature, ou guide ecologique.");
    speak("Choisis maintenant une intelligence artificielle : analyse des dechets, voix de la nature, ou guide ecologique.");
  };

  document.querySelectorAll(".eco-ai-choice").forEach(function (btn) {
    btn.onclick = function () {
      selectedAI = btn.dataset.ai;
      operation.classList.add("show");
      imageZone.style.display = "none";

      if (selectedAI === "dechets") {
        title.innerHTML = "Analyse image dechets";
        imageZone.style.display = "block";
        result.innerHTML = '<div class="eco-ai-empty-state">Ajoute une image pour obtenir une lecture rapide du type de dechet, du risque et des actions conseillees.</div>';
        say("Analyse image dechets activee. Ajoute une photo et je vais lancer une vraie analyse IA.");
        speak("Analyse image dechets activee. Ajoute une photo et je vais lancer une vraie analyse IA.");
      }

      if (selectedAI === "nature") {
        title.innerHTML = "Voix de la nature";
        const text = "Je suis la mer. Chaque bouteille, chaque sac plastique et chaque canette menace les poissons, les oiseaux et les tortues. Protegez-moi aujourd'hui pour garder un littoral vivant demain.";
        result.innerHTML = '<div class="eco-ai-result-line">' + text + '</div>';
        say(text);
        speak(text);
      }

      if (selectedAI === "guide") {
        title.innerHTML = "Guide ecologique";
        const text = "Conseil EcoBot : commence par les zones les plus frequentees, ajoute des poubelles visibles, organise un nettoyage cible, puis suis les resultats chaque semaine avec les signalements.";
        result.innerHTML = '<div class="eco-ai-result-line">' + text + '</div>';
        say(text);
        speak(text);
      }
    };
  });

  document.getElementById("ecoAiAnalyze").onclick = async function () {
    const fileInput = document.getElementById("ecoWasteImage");
    const file = fileInput.files[0];

    if (!file) {
      result.innerHTML = "Ajoute une image d'abord.";
      say("Ajoute une image d'abord pour que je puisse faire une vraie analyse.");
      speak("Ajoute une image d'abord pour que je puisse faire une vraie analyse.");
      return;
    }

    const formData = new FormData();
    formData.append("image", file);

    result.innerHTML = '<div class="eco-ai-loading"><span></span><span></span><span></span>Analyse en cours...</div>';
    say("Analyse reelle en cours. J envoie l image au modele IA.");
    speak("Analyse reelle en cours. J envoie l image au modele IA.");

    try {
      const response = await fetch("/admin/ai/analyze-waste", {
        method: "POST",
        body: formData
      });

      const data = await response.json();

      if (!response.ok || data.error) {
        result.innerHTML = data.error || "Erreur pendant l'analyse.";
        say(data.error || "Erreur pendant l analyse.");
        speak(data.error || "Erreur pendant l analyse.");
        return;
      }

      result.innerHTML = formatAnalysis(data.analysis);
      say("Analyse terminee. Consulte le resultat detaille ci-dessous.");
      speak("Analyse terminee. Consulte le resultat detaille ci-dessous.");

    } catch (e) {
      result.innerHTML = "Erreur reseau ou serveur.";
      say("Erreur reseau ou serveur pendant l analyse.");
      speak("Erreur reseau ou serveur pendant l analyse.");
    }
  };

  let isDragging = false;
  let offsetX = 0;
  let offsetY = 0;

  robot.addEventListener("mousedown", function (e) {
    return;
    isDragging = true;
    offsetX = e.clientX - stage.offsetLeft;
    offsetY = e.clientY - stage.offsetTop;
  });

  document.addEventListener("mousemove", function (e) {
    if (!isDragging) return;
    stage.style.left = e.clientX - offsetX + "px";
    stage.style.top = e.clientY - offsetY + "px";
    stage.style.right = "auto";
    stage.style.bottom = "auto";
  });

  document.addEventListener("mouseup", function () {
    isDragging = false;
  });
});
