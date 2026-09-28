var supabaseClient;

function obterConfigCliente() {
  if (window.__CONFIG__ && window.__CONFIG__.SUPABASE_URL && window.__CONFIG__.SUPABASE_KEY) {
    return window.__CONFIG__;
  }

  var configNode = document.getElementById("defast-client-config");
  if (!configNode) return null;

  try {
    return JSON.parse(configNode.textContent || "{}");
  } catch (err) {
    console.error("Falha ao carregar configuracao do cliente:", err);
    return null;
  }
}

(function () {
  var cfg = obterConfigCliente();
  if (!cfg || !cfg.SUPABASE_URL || !cfg.SUPABASE_KEY) return;
  if (!window.supabase) return;

  var createClient = window.supabase.createClient;
  supabaseClient = createClient(cfg.SUPABASE_URL, cfg.SUPABASE_KEY);

  var formReset = document.getElementById("form-reset");
  if (formReset) formReset.addEventListener("submit", handleNewPassword);
})();

async function handleNewPassword(e) {
  e.preventDefault();
  if (!supabaseClient) return;

  var newPassword = document.getElementById("new-password").value;
  var confirmPassword = document.getElementById("confirm-password").value;
  var btn = document.getElementById("btn-reset");

  if (newPassword !== confirmPassword) {
    mostrarMsg("erro", "As senhas não coincidem.");
    return;
  }

  try {
    btn.innerText = "Atualizando...";
    btn.disabled = true;

    var result = await supabaseClient.auth.updateUser({
      password: newPassword,
    });
    if (result.error) throw result.error;

    mostrarMsg("sucesso", "Senha atualizada com sucesso! Redirecionando...");
    setTimeout(function () {
      window.location.href = "area-cliente";
    }, 2000);
  } catch (err) {
    mostrarMsg("erro", err.message);
    btn.innerText = "Atualizar Senha";
    btn.disabled = false;
  }
}

function mostrarMsg(tipo, texto) {
  var box = document.getElementById("msg-reset");
  box.innerText = texto;
  box.className = "p-4 mb-4 rounded-md text-sm text-center";

  if (tipo === "erro") {
    box.classList.add("bg-red-50", "text-red-700");
  } else {
    box.classList.add("bg-green-50", "text-green-700");
  }

  box.classList.remove("hidden");
}
