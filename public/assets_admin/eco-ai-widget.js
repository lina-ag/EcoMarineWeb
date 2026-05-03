document.addEventListener("DOMContentLoaded", function () {
  const widget = document.createElement("div");

  widget.innerHTML = `
    <button class="eco-ai-btn" id="ecoAiOpen">AI</button>

    <div class="eco-robot-stage" id="ecoRobotStage">
      <div class="eco-robot-bubble" id="ecoRobotBubble">
        Bonjour et bienvenue sur Eco Marine Dechets.
      </div>

      <div class="eco-robot-body" id="ecoRobotBody">
        <div class="eco-robot-arm eco-robot-arm-left"></div>
        <div class="eco-robot-arm eco-robot-arm-right"></div>
        <div class="eco-robot-head">
          <div class="eco-robot-eye"></div>
          <div class="eco-robot-eye"></div>
        </div>
        <div class="eco-robot-mouth"></div>
        <div class="eco-robot-base">EcoBot</div>
      </div>

      <div class="eco-robot-panel">
        <button class="eco-ai-small-btn" id="ecoAiNext">Suivant</button>

        <div class="eco-ai-choices" id="ecoAiChoices" style="display:none;">
          <button class="eco-ai-choice" data-ai="dechets">Analyse image dechets</button>
          <button class="eco-ai-choice" data-ai="nature">Voix de la nature</button>
          <button class="eco-ai-choice" data-ai="guide">Guide ecologique</button>
        </div>

        <div class="eco-ai-api-box" id="ecoAiOperation">
          <strong id="ecoAiSelectedTitle">Operation IA</strong>

          <div id="ecoAiImageZone" style="display:none;">
            <input class="eco-ai-input" id="ecoWasteImage" type="file" accept="image/png,image/jpeg,image/webp">
            <button class="eco-ai-small-btn" id="ecoAiAnalyze">Analyser l'image reellement</button>
          </div>

          <button class="eco-ai-small-btn" id="ecoAiSpeak">Lire le resultat</button>

          <div class="eco-ai-result" id="ecoAiResult">
            Choisis une intelligence artificielle.
          </div>
        </div>
      </div>

      <button class="eco-robot-close" id="ecoRobotClose">X</button>
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
        result.innerHTML = "Ajoute une image JPG, PNG ou WEBP puis clique sur analyser.";
        say("Analyse image dechets activee. Ajoute une photo et je vais lancer une vraie analyse IA.");
        speak("Analyse image dechets activee. Ajoute une photo et je vais lancer une vraie analyse IA.");
      }

      if (selectedAI === "nature") {
        title.innerHTML = "Voix de la nature";
        const text = "Je suis la mer. Chaque bouteille, chaque sac plastique et chaque canette menace les poissons, les oiseaux et les tortues. Protegez-moi aujourd'hui pour garder un littoral vivant demain.";
        result.innerHTML = text;
        say(text);
        speak(text);
      }

      if (selectedAI === "guide") {
        title.innerHTML = "Guide ecologique";
        const text = "Conseil EcoBot : commence par les zones les plus frequentees, ajoute des poubelles visibles, organise un nettoyage cible, puis suis les resultats chaque semaine avec les signalements.";
        result.innerHTML = text;
        say(text);
        speak(text);
      }
    };
  });

  document.getElementById("ecoAiSpeak").onclick = function () {
    speak(currentText);
  };

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

    result.innerHTML = "Analyse en cours...";
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

      let prefix = data.fallback ? "Analyse estimee :<br><br>" : "Analyse IA reelle :<br><br>";
      result.innerHTML = prefix + data.analysis.replace(/\n/g, "<br>");
      say("Analyse terminee. " + data.analysis);
      speak("Analyse terminee. " + data.analysis);

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